#!/usr/bin/env python3
"""
One-click local host for Visitor Pass Management System.

No XAMPP required. Uses portable PHP + SQLite.
Can install as a Windows auto-start service (Scheduled Task at startup)
and run the console minimized/hidden.
"""

from __future__ import annotations

import argparse
import ctypes
import os
import shutil
import socket
import subprocess
import sys
import threading
import time
import urllib.error
import urllib.request
import webbrowser
from pathlib import Path

# ---------------------------------------------------------------------------
# Paths / constants
# ---------------------------------------------------------------------------
ROOT = Path(__file__).resolve().parent
PHP_DIR = ROOT / "php"
PHP_EXE = PHP_DIR / "php.exe"
APP_DIR = ROOT / "codecanyon-24643230-visitor-pass-management-system"
PUBLIC_DIR = APP_DIR / "public"
ENV_PATH = APP_DIR / ".env"
ENV_REMOTE_BACKUP = APP_DIR / ".env.remote.backup"
SQLITE_PATH = APP_DIR / "database" / "database.sqlite"
INSTALLED_MARKER = APP_DIR / "storage" / "installed"
SETUP_MARKER = ROOT / "runtime" / ".local_setup_done"
RUNTIME_DIR = ROOT / "runtime"
PHP_TMP = PHP_DIR / "tmp"
PHP_LOGS = PHP_DIR / "logs"
PHP_RUNTIME_INI = RUNTIME_DIR / "php-local.ini"
PID_FILE = RUNTIME_DIR / "server.pid"
PORT_FILE = RUNTIME_DIR / "server.port"
LOG_FILE = RUNTIME_DIR / "server.log"
SERVICE_VBS = ROOT / "run_service.vbs"
SERVICE_MARKER = RUNTIME_DIR / ".service_installed"

TASK_NAME = "VisitorPassLocalHost"
SERVICE_DISPLAY = "Visitor Pass Local Host"

# PHP binds to loopback; browser/APP_URL use friendly local DNS name.
# Use .localhost (NOT .local): .local is mDNS and can resolve to LAN devices
# (e.g. a DVR/NVR). *.localhost always maps to this PC (127.0.0.1).
BIND_HOST = "127.0.0.1"
LOCAL_DNS = "visitorpass.localhost"
OLD_LOCAL_DNS_NAMES = ("visitorpass.local",)  # remove these from hosts if present
HOSTS_MARKER = "# VisitorPassLocalHost"
# Port 80 = default HTTP so the URL has no :port (http://visitorpass.localhost)
# Binding to 80 requires admin (UAC) on Windows.
PORT = 80
# Unique fallback — avoid common DVR/camera ports (8080, 8090, etc.)
FALLBACK_PORT = 8750
LEGACY_PORT_RANGE = range(8000, 8020)  # previous launcher default

# Back-compat alias used by bind/port checks
HOST = BIND_HOST

ADMIN_EMAIL = "admin@example.com"
ADMIN_PASSWORD = "123456"

# When True, avoid interactive prompts (service / background mode)
HEADLESS = False


def banner(msg: str = "") -> None:
    print()
    print("=" * 64)
    if msg:
        print(msg)
        print("=" * 64)


def ok(msg: str) -> None:
    print(f"  [OK] {msg}")


def info(msg: str) -> None:
    print(f"  [..] {msg}")


def err(msg: str) -> None:
    print(f"  [ERROR] {msg}")


def die(msg: str, code: int = 1) -> None:
    err(msg)
    _log(f"FATAL: {msg}")
    if not HEADLESS:
        print()
        try:
            input("Press Enter to exit...")
        except Exception:
            pass
    sys.exit(code)


def _log(msg: str) -> None:
    try:
        RUNTIME_DIR.mkdir(parents=True, exist_ok=True)
        with LOG_FILE.open("a", encoding="utf-8") as fh:
            fh.write(f"{time.strftime('%Y-%m-%d %H:%M:%S')}  {msg}\n")
    except Exception:
        pass


def is_admin() -> bool:
    try:
        return bool(ctypes.windll.shell32.IsUserAnAdmin())
    except Exception:
        return False


def find_python_command() -> list[str]:
    """Return argv prefix to re-invoke this script with the same Python."""
    # Prefer current interpreter
    exe = Path(sys.executable)
    if exe.name.lower() == "pythonw.exe":
        # Prefer console python next to pythonw for reliability
        console = exe.with_name("python.exe")
        if console.exists():
            return [str(console)]
    return [sys.executable]


def ensure_dirs() -> None:
    for path in (
        RUNTIME_DIR,
        PHP_TMP,
        PHP_LOGS,
        APP_DIR / "storage" / "framework" / "cache" / "data",
        APP_DIR / "storage" / "framework" / "sessions",
        APP_DIR / "storage" / "framework" / "views",
        APP_DIR / "storage" / "logs",
        APP_DIR / "storage" / "app" / "public",
        APP_DIR / "bootstrap" / "cache",
        APP_DIR / "public" / "uploads",
        APP_DIR / "database",
    ):
        path.mkdir(parents=True, exist_ok=True)


def port_in_use(host: str, port: int) -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.settimeout(0.5)
        return sock.connect_ex((host, port)) == 0


def find_free_port(start: int = FALLBACK_PORT, attempts: int = 20) -> int:
    for port in range(start, start + attempts):
        if not port_in_use(BIND_HOST, port):
            return port
    die(f"No free port found between {start} and {start + attempts - 1}")


def ports_to_scan() -> list[int]:
    """Ports this app may use (never broad-scan DVR ranges like 8080–8099)."""
    ordered: list[int] = [PORT, FALLBACK_PORT]
    ordered.extend(range(FALLBACK_PORT, FALLBACK_PORT + 5))
    ordered.extend(LEGACY_PORT_RANGE)
    seen: set[int] = set()
    out: list[int] = []
    for p in ordered:
        if p not in seen:
            seen.add(p)
            out.append(p)
    return out


def choose_listen_port() -> int:
    """Prefer port 80 (clean URL). Fall back if busy."""
    if not port_in_use(BIND_HOST, PORT):
        return PORT
    if not HEADLESS:
        info(f"Port {PORT} is busy (another app) — using port {FALLBACK_PORT}")
    return find_free_port(FALLBACK_PORT)


def pid_is_running(pid: int) -> bool:
    try:
        result = subprocess.run(
            ["tasklist", "/FI", f"PID eq {pid}", "/NH"],
            capture_output=True,
            text=True,
        )
        out = (result.stdout or "") + (result.stderr or "")
        return str(pid) in out and "No tasks" not in out
    except Exception:
        return False


def is_our_server_on_port(port: int) -> bool:
    """
    True only if THIS Visitor Pass app is responding on the port.
    Prevents opening a DVR/camera UI that happens to share a port number.
    """
    if not port_in_use(BIND_HOST, port):
        return False
    try:
        host_header = LOCAL_DNS if port == 80 else f"{LOCAL_DNS}:{port}"
        req = urllib.request.Request(
            f"http://{BIND_HOST}:{port}/",
            headers={
                "Host": host_header,
                "User-Agent": "VisitorPassLocalHost/1.0",
            },
            method="GET",
        )
        with urllib.request.urlopen(req, timeout=2.0) as resp:
            body = resp.read(16000).decode("utf-8", errors="ignore")
    except (urllib.error.URLError, urllib.error.HTTPError, TimeoutError, OSError):
        return False
    except Exception:
        return False

    low = body.lower()
    # Strong markers from this Laravel Visitor Pass frontend / stack
    markers = (
        "visitor pass",
        "plusjakartasans",
        "izitoast",
        "site_logo",
        "csrf-token",
        "check in",
        "check-in",
        "frontend/css/style.css",
        "frontend/js/script.js",
    )
    hits = sum(1 for m in markers if m in low)
    # Reject obvious non-app pages (DVR / camera appliances often redirect)
    reject = ("dvr", "nvr", "hikvision", "dahua", "webcam", "ipcam", "rtsp")
    if hits < 2:
        return False
    if hits <= 2 and any(r in low for r in reject):
        return False
    return True


def _pid_looks_like_php(pid: int) -> bool:
    try:
        result = subprocess.run(
            ["tasklist", "/FI", f"PID eq {pid}", "/NH", "/FO", "CSV"],
            capture_output=True,
            text=True,
        )
        out = (result.stdout or "").lower()
        return "php.exe" in out
    except Exception:
        return False


def get_running_port() -> int | None:
    """Return port only if OUR Visitor Pass server is actually running there."""
    # 1) Tracked PID + port from last successful start
    if PORT_FILE.exists() and PID_FILE.exists():
        try:
            port = int(PORT_FILE.read_text(encoding="utf-8").strip())
            pid = int(PID_FILE.read_text(encoding="utf-8").strip())
            if pid_is_running(pid) and port_in_use(BIND_HOST, port) and _pid_looks_like_php(pid):
                # Warm-up: PHP may not answer for 1–2s after bind
                for _ in range(8):
                    if is_our_server_on_port(port):
                        return port
                    time.sleep(0.35)
                # php.exe we started is still listening — accept even if page is slow
                return port
        except Exception:
            pass

    # 2) PORT file alone — only if HTTP proves it is Visitor Pass (never a DVR)
    if PORT_FILE.exists():
        try:
            port = int(PORT_FILE.read_text(encoding="utf-8").strip())
            if is_our_server_on_port(port):
                return port
        except Exception:
            pass

    # 3) Scan our ports only, require HTTP fingerprint (never open a DVR UI)
    for port in ports_to_scan():
        if is_our_server_on_port(port):
            return port
    return None


def hosts_file_path() -> Path:
    system_root = os.environ.get("SystemRoot", r"C:\Windows")
    return Path(system_root) / "System32" / "drivers" / "etc" / "hosts"


def local_dns_configured() -> bool:
    """True if Windows hosts file maps LOCAL_DNS to loopback."""
    path = hosts_file_path()
    try:
        text = path.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        return False
    for raw in text.splitlines():
        line = raw.strip()
        if not line or line.startswith("#"):
            continue
        # Strip inline comments
        if "#" in line:
            line = line.split("#", 1)[0].strip()
        parts = line.split()
        if len(parts) < 2:
            continue
        ip = parts[0]
        names = [p.lower() for p in parts[1:]]
        if LOCAL_DNS.lower() in names and ip in ("127.0.0.1", "::1"):
            return True
    return False


def write_hosts_entry() -> bool:
    """
    Write LOCAL_DNS -> 127.0.0.1 into hosts file.
    Also removes obsolete visitorpass.local entries (mDNS-conflicting).
    Requires admin. Returns success.
    """
    path = hosts_file_path()
    try:
        text = path.read_text(encoding="utf-8", errors="ignore") if path.exists() else ""
    except Exception as exc:
        _log(f"Cannot read hosts file: {exc}")
        return False

    drop_names = {LOCAL_DNS.lower(), *(n.lower() for n in OLD_LOCAL_DNS_NAMES)}
    new_lines: list[str] = []
    for raw in text.splitlines():
        stripped = raw.strip()
        if HOSTS_MARKER in raw:
            continue
        if stripped and not stripped.startswith("#"):
            check = stripped.split("#", 1)[0].strip()
            parts = check.split()
            if len(parts) >= 2:
                names = [p.lower() for p in parts[1:]]
                if any(n in drop_names for n in names):
                    continue
        new_lines.append(raw)

    new_lines.append(HOSTS_MARKER)
    new_lines.append(f"127.0.0.1\t{LOCAL_DNS}")
    # Keep old wrong name pointed at loopback too, in case bookmarks remain
    for old in OLD_LOCAL_DNS_NAMES:
        new_lines.append(f"127.0.0.1\t{old}")

    try:
        path.write_text("\r\n".join(new_lines) + "\r\n", encoding="utf-8")
        return local_dns_configured()
    except PermissionError:
        _log("Permission denied writing hosts file")
        return False
    except Exception as exc:
        _log(f"Cannot write hosts file: {exc}")
        return False


def ensure_local_dns() -> bool:
    """
    Ensure visitorpass.localhost is used (hosts file + browser-safe TLD).
    *.localhost already resolves to 127.0.0.1 in modern browsers even without hosts.
    """
    if local_dns_configured():
        if not HEADLESS:
            ok(f"Local DNS ready: {public_url(PORT)}")
        return True

    if is_admin() and write_hosts_entry():
        if not HEADLESS:
            ok(f"Local DNS added: {LOCAL_DNS} -> 127.0.0.1")
        return True

    if not HEADLESS:
        info(f"Approving UAC once adds local name: {LOCAL_DNS}")
    try:
        rc = ctypes.windll.shell32.ShellExecuteW(
            None,
            "runas",
            sys.executable,
            f'"{ROOT / "local_server.py"}" --ensure-hosts',
            str(ROOT),
            1,
        )
        if rc > 32:
            for _ in range(30):
                time.sleep(0.25)
                if local_dns_configured():
                    if not HEADLESS:
                        ok(f"Local DNS added: {LOCAL_DNS} -> 127.0.0.1")
                    return True
    except Exception as exc:
        _log(f"Hosts elevation failed: {exc}")

    if write_hosts_entry():
        if not HEADLESS:
            ok(f"Local DNS added: {LOCAL_DNS} -> 127.0.0.1")
        return True

    # *.localhost still works in Chrome/Edge/Firefox without hosts
    if LOCAL_DNS.endswith(".localhost"):
        if not HEADLESS:
            info(f"Hosts file not updated — browsers still resolve {LOCAL_DNS} to this PC")
            ok(f"Using: {public_url(PORT)}")
        return True

    if not HEADLESS:
        err(f"Could not register {LOCAL_DNS} (admin required once).")
        info(f"Manual: open Notepad as Admin -> edit {hosts_file_path()}")
        info(f"  Add line:  127.0.0.1  {LOCAL_DNS}")
        info("Falling back to 127.0.0.1 for this session.")
    return False


def public_host() -> str:
    """Hostname shown in browser / APP_URL."""
    # *.localhost is defined to loop back; safe even if hosts write failed
    if LOCAL_DNS.endswith(".localhost"):
        return LOCAL_DNS
    return LOCAL_DNS if local_dns_configured() else BIND_HOST


def public_url(port: int | None = None) -> str:
    """Public site URL. Port 80 is omitted so the address is just the host name."""
    p = port if port is not None else PORT
    host = public_host()
    if p == 80:
        return f"http://{host}"
    if p == 443:
        return f"https://{host}"
    return f"http://{host}:{p}"


def needs_admin_for_port(port: int) -> bool:
    """Windows requires elevation to bind privileged ports (< 1024)."""
    return port < 1024 and not is_admin()


def relaunch_elevated(cli_args: str, show_cmd: int = 1) -> bool:
    """
    Re-launch this script elevated (UAC). show_cmd: 0=hidden, 1=normal, 2=minimized.
    Returns True if elevation was accepted / launched.
    """
    try:
        rc = ctypes.windll.shell32.ShellExecuteW(
            None,
            "runas",
            sys.executable,
            f'"{ROOT / "local_server.py"}" {cli_args}',
            str(ROOT),
            show_cmd,
        )
        return rc > 32
    except Exception as exc:
        _log(f"Elevation failed: {exc}")
        return False


def write_portable_php_ini(with_imagick: bool = True) -> None:
    """Build a portable php.ini with absolute paths (no XAMPP)."""
    ext_dir = (PHP_DIR / "ext").resolve()
    tmp_dir = PHP_TMP.resolve()
    log_file = (PHP_LOGS / "php_error_log").resolve()
    ca_bundle = (ROOT / "bin" / "curl-ca-bundle.crt").resolve()

    ca_line = f'curl.cainfo="{ca_bundle}"\nopenssl.cafile="{ca_bundle}"\n' if ca_bundle.exists() else ""
    imagick_line = "extension=imagick\n" if with_imagick else ";extension=imagick\n"

    content = f"""[PHP]
engine=On
short_open_tag=Off
precision=14
output_buffering=4096
zlib.output_compression=Off
implicit_flush=Off
unserialize_callback_func=
serialize_precision=-1
disable_functions=
disable_classes=
zend.enable_gc=On
zend.exception_ignore_args=On
zend.exception_string_param_max_len=15
expose_php=On
max_execution_time=300
max_input_time=120
memory_limit=512M
error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT
display_errors=On
display_startup_errors=On
log_errors=On
error_log="{log_file}"
variables_order="GPCS"
request_order="GP"
register_argc_argv=Off
auto_globals_jit=On
post_max_size=64M
auto_prepend_file=
auto_append_file=
default_mimetype="text/html"
default_charset="UTF-8"
include_path="."
doc_root=
user_dir=
enable_dl=Off
file_uploads=On
upload_tmp_dir="{tmp_dir}"
upload_max_filesize=64M
max_file_uploads=20
allow_url_fopen=On
allow_url_include=Off
default_socket_timeout=60

extension_dir="{ext_dir}"

extension=bz2
extension=curl
extension=fileinfo
extension=gd
extension=gettext
extension=mbstring
extension=exif
extension=mysqli
extension=pdo_mysql
extension=pdo_sqlite
extension=sqlite3
extension=openssl
extension=ftp
extension=zip
{imagick_line}
[CLI Server]
cli_server.color=On

[Date]
date.timezone=Asia/Kolkata

[Session]
session.save_handler=files
session.save_path="{tmp_dir}"
session.use_strict_mode=0
session.use_cookies=1
session.use_only_cookies=1
session.name=PHPSESSID
session.auto_start=0
session.cookie_lifetime=0
session.cookie_path=/
session.serialize_handler=php
session.gc_probability=1
session.gc_divisor=1000
session.gc_maxlifetime=1440

[bcmath]
bcmath.scale=0

[curl]
{ca_line}[openssl]
"""
    PHP_RUNTIME_INI.write_text(content, encoding="utf-8")
    if not HEADLESS:
        ok(f"Portable PHP config ready: {PHP_RUNTIME_INI.name}")


def php_cmd(*args: str) -> list[str]:
    return [str(PHP_EXE), "-c", str(PHP_RUNTIME_INI), *args]


def run_php(args: list[str], check: bool = True, capture: bool = False) -> subprocess.CompletedProcess:
    env = os.environ.copy()
    env["PATH"] = str(PHP_DIR) + os.pathsep + env.get("PATH", "")
    env["PHPRC"] = str(RUNTIME_DIR)
    return subprocess.run(
        php_cmd(*args),
        cwd=str(APP_DIR),
        env=env,
        check=check,
        capture_output=capture,
        text=True,
    )


COMPOSER_PHAR = ROOT / "runtime" / "composer.phar"
COMPOSER_HOME = ROOT / "runtime" / "composer-home"
COMPOSER_URL = "https://getcomposer.org/download/latest-stable/composer.phar"


def download_composer_phar() -> None:
    """Download composer.phar into runtime/ (once)."""
    RUNTIME_DIR.mkdir(parents=True, exist_ok=True)
    if COMPOSER_PHAR.exists() and COMPOSER_PHAR.stat().st_size > 100_000:
        return
    if not HEADLESS:
        info("Downloading Composer (one-time)...")
    _log(f"Downloading Composer from {COMPOSER_URL}")
    try:
        req = urllib.request.Request(
            COMPOSER_URL,
            headers={"User-Agent": "VisitorPassLocalHost/1.0"},
        )
        with urllib.request.urlopen(req, timeout=120) as resp:
            data = resp.read()
        if len(data) < 100_000:
            die("Downloaded composer.phar looks too small / corrupt. Check internet connection.")
        COMPOSER_PHAR.write_bytes(data)
        if not HEADLESS:
            ok(f"Composer ready: {COMPOSER_PHAR.name}")
    except Exception as exc:
        die(
            "Could not download Composer. Need internet on first install.\n"
            f"  Error: {exc}\n"
            "  Fix: connect to internet and run START.bat again,\n"
            "  OR manually copy the vendor folder from a working PC into:\n"
            f"  {APP_DIR / 'vendor'}"
        )


def ensure_composer_vendor() -> None:
    """
    vendor/ is intentionally not in GitHub (.gitignore).
    On a fresh clone, install PHP dependencies with Composer using portable PHP.
    """
    autoload = APP_DIR / "vendor" / "autoload.php"
    if autoload.exists():
        return

    if not (APP_DIR / "composer.json").exists():
        die(f"composer.json missing in app folder: {APP_DIR}")

    if not HEADLESS:
        banner("Missing vendor/ folder")
        print("  This is normal after git clone (vendor is not uploaded to GitHub).")
        print("  Installing PHP packages with Composer now...")
        print("  First run needs internet and may take several minutes.")
        print()
    _log("vendor/ missing — running composer install")

    # Ensure php.ini exists before invoking PHP for Composer
    if not PHP_RUNTIME_INI.exists():
        write_portable_php_ini()

    download_composer_phar()
    COMPOSER_HOME.mkdir(parents=True, exist_ok=True)

    env = os.environ.copy()
    env["PATH"] = str(PHP_DIR) + os.pathsep + env.get("PATH", "")
    env["PHPRC"] = str(RUNTIME_DIR)
    env["COMPOSER_HOME"] = str(COMPOSER_HOME)
    # Avoid interactive prompts / telemetry noise
    env["COMPOSER_DISABLE_XDEBUG_WARN"] = "1"
    env["COMPOSER_NO_INTERACTION"] = "1"

    # --no-scripts: artisan package:discover needs .env which is written later
    cmd = php_cmd(
        str(COMPOSER_PHAR),
        "install",
        "--no-dev",
        "--prefer-dist",
        "--no-interaction",
        "--no-scripts",
        "--optimize-autoloader",
    )
    if not HEADLESS:
        info("Running: composer install --no-dev (please wait)...")
    try:
        result = subprocess.run(
            cmd,
            cwd=str(APP_DIR),
            env=env,
            check=False,
            capture_output=True,
            text=True,
        )
    except Exception as exc:
        die(f"Failed to run Composer: {exc}")

    out = ((result.stdout or "") + "\n" + (result.stderr or "")).strip()
    if out:
        _log(out[-8000:])
    if result.returncode != 0 or not autoload.exists():
        if not HEADLESS and out:
            # Show the most useful tail of Composer output
            print(out[-4000:])
        die(
            "Composer install failed — vendor/ still missing.\n"
            "  Common causes: no internet, firewall, or PHP openssl/zip missing.\n"
            "  Manual fix on this PC (in the app folder):\n"
            f"    cd /d \"{APP_DIR}\"\n"
            f"    \"{PHP_EXE}\" \"{COMPOSER_PHAR}\" install --no-dev\n"
            "  Or copy the full vendor folder from a PC where the app already works."
        )

    if not HEADLESS:
        ok("Composer dependencies installed (vendor/ ready)")
    _log("composer install completed successfully")


def check_prerequisites() -> None:
    if not HEADLESS:
        banner("Visitor Pass Management System - Local Host")
        print("  No XAMPP. Portable PHP + SQLite + Python launcher.")
        print()

    if not PHP_EXE.exists():
        die(f"Portable PHP not found: {PHP_EXE}")
    if not HEADLESS:
        ok(f"PHP found: {PHP_EXE}")

    if not APP_DIR.exists():
        die(f"App folder not found: {APP_DIR}")
    if not HEADLESS:
        ok(f"App found: {APP_DIR.name}")

    # vendor is gitignored — auto-install from composer.lock when missing
    ensure_composer_vendor()
    if not (APP_DIR / "vendor" / "autoload.php").exists():
        die("Composer vendor/ folder missing after install attempt.")
    if not HEADLESS:
        ok("Composer dependencies present")

    if not PUBLIC_DIR.exists():
        die(f"public/ folder missing: {PUBLIC_DIR}")

    try:
        result = run_php(["-v"], capture=True)
        combined = (result.stdout or "") + (result.stderr or "")
        if result.returncode != 0 or (
            "Unable to load dynamic library" in combined and "imagick" in combined.lower()
        ):
            if not HEADLESS:
                info("ImageMagick failed to load; continuing without it")
            write_portable_php_ini(with_imagick=False)
            result = run_php(["-v"], capture=True)
            combined = (result.stdout or "") + (result.stderr or "")
        first = (result.stdout or result.stderr or "").splitlines()[0] if (result.stdout or result.stderr) else "PHP"
        if result.returncode != 0:
            die(f"Failed to run PHP:\n{combined}")
        if not HEADLESS:
            ok(first.strip())
    except Exception as exc:
        die(f"Failed to run PHP: {exc}")

    result = run_php(["-m"], capture=True)
    modules = (result.stdout or "").lower()
    required = ["pdo_sqlite", "sqlite3", "openssl", "mbstring", "tokenizer", "curl", "fileinfo", "gd", "zip"]
    missing = [m for m in required if m not in modules]
    if missing:
        die("Missing PHP extensions: " + ", ".join(missing))
    if not HEADLESS:
        ok("Required PHP extensions loaded")
        if "imagick" in modules:
            ok("Imagick available")
        else:
            info("Imagick not loaded (image thumbnails may be limited)")


def write_local_env(port: int) -> None:
    if ENV_PATH.exists() and not ENV_REMOTE_BACKUP.exists():
        if "mysql" in ENV_PATH.read_text(encoding="utf-8", errors="ignore").lower():
            shutil.copy2(ENV_PATH, ENV_REMOTE_BACKUP)
            if not HEADLESS:
                ok(f"Backed up previous .env -> {ENV_REMOTE_BACKUP.name}")

    app_url = public_url(port)
    content = f"""APP_NAME="Visitor Pass"
APP_ENV=local
APP_KEY=base64:Q1SpHbzZdugpYQALb9xOyOZqzcsAI6SjzCcZrVn6QCI=
APP_DEBUG=true
APP_LOG_LEVEL=debug
APP_URL={app_url}

DB_CONNECTION=sqlite
DB_DATABASE="{SQLITE_PATH.as_posix()}"
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@localhost"
MAIL_FROM_NAME="${{APP_NAME}}"

APP_TIMEZONE=Asia/Kolkata

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=

JWT_SECRET=HvOs0ZZKg3eAMNrBnZU6kPDQqdvgsmmtXG4ATyh2NiZqolFzneUyHjCImT5GpUym

TWILIO_AUTH_TOKEN=
TWILIO_FROM=
TWILIO_ACCOUNT_SID=

FCM_SECRET_KEY=
FCM_TOPIC=visitor

FILESYSTEM_DISK=public
"""
    ENV_PATH.write_text(content, encoding="utf-8")
    if not HEADLESS:
        ok(f".env configured for local SQLite ({app_url})")


def ensure_sqlite() -> None:
    if not SQLITE_PATH.exists():
        SQLITE_PATH.touch()
        if not HEADLESS:
            ok(f"Created SQLite database: {SQLITE_PATH.name}")
    elif not HEADLESS:
        ok(f"SQLite database exists: {SQLITE_PATH.name}")


def first_time_setup() -> None:
    if SETUP_MARKER.exists() and INSTALLED_MARKER.exists() and SQLITE_PATH.exists() and SQLITE_PATH.stat().st_size > 0:
        if not HEADLESS:
            ok("Local setup already completed (skipping migrate/seed)")
        return

    if not HEADLESS:
        info("First-time setup: migrating and seeding database...")
    _log("Running first-time migrate --seed")

    for artisan_cmd in (
        ["artisan", "config:clear"],
        ["artisan", "cache:clear"],
        ["artisan", "route:clear"],
        ["artisan", "view:clear"],
    ):
        try:
            run_php(artisan_cmd, check=False, capture=True)
        except Exception:
            pass

    result = run_php(["artisan", "migrate", "--force", "--seed"], check=False, capture=True)
    if result.returncode != 0:
        out = (result.stdout or "") + "\n" + (result.stderr or "")
        if not HEADLESS:
            print(out)
        _log(out)
        die("Database migrate/seed failed. See output above / runtime/server.log.")
    if not HEADLESS:
        ok("Database migrated and seeded")

    run_php(["artisan", "storage:link"], check=False, capture=True)
    if not HEADLESS:
        ok("Storage link ready")

    INSTALLED_MARKER.write_text("installed-by-local-server\n", encoding="utf-8")
    SETUP_MARKER.write_text(time.strftime("%Y-%m-%d %H:%M:%S"), encoding="utf-8")
    if not HEADLESS:
        ok("App marked as installed")


def open_browser_later(url: str, delay: float = 1.5) -> None:
    def _open() -> None:
        time.sleep(delay)
        try:
            webbrowser.open(url)
        except Exception:
            pass

    threading.Thread(target=_open, daemon=True).start()


def write_pid(pid: int, port: int) -> None:
    PID_FILE.write_text(str(pid), encoding="utf-8")
    PORT_FILE.write_text(str(port), encoding="utf-8")


def clear_pid() -> None:
    for path in (PID_FILE, PORT_FILE):
        try:
            if path.exists():
                path.unlink()
        except Exception:
            pass


def stop_server_processes() -> int:
    """Stop OUR PHP/artisan server only (never kill unrelated DVR/camera apps)."""
    stopped = 0
    if PID_FILE.exists():
        try:
            pid = int(PID_FILE.read_text(encoding="utf-8").strip())
            if _pid_looks_like_php(pid) or pid_is_running(pid):
                result = subprocess.run(
                    ["taskkill", "/F", "/T", "/PID", str(pid)],
                    capture_output=True,
                    text=True,
                )
                if result.returncode == 0:
                    stopped += 1
        except Exception:
            pass

    for port in ports_to_scan():
        # Only ports where Visitor Pass itself is responding (skip DVR etc.)
        if not is_our_server_on_port(port):
            continue
        try:
            result = subprocess.run(
                ["cmd", "/c", f'netstat -ano | findstr ":{port} " | findstr LISTENING'],
                capture_output=True,
                text=True,
            )
            for line in (result.stdout or "").splitlines():
                parts = line.split()
                if not parts:
                    continue
                pid_s = parts[-1]
                if not (pid_s.isdigit() and pid_s != "0"):
                    continue
                pid = int(pid_s)
                # Only terminate PHP (our stack) — never a DVR binary
                if not _pid_looks_like_php(pid):
                    continue
                kill = subprocess.run(
                    ["taskkill", "/F", "/T", "/PID", pid_s],
                    capture_output=True,
                    text=True,
                )
                if kill.returncode == 0:
                    stopped += 1
        except Exception:
            pass

    clear_pid()
    return stopped


def start_server(port: int, open_browser: bool = True) -> None:
    # Port 80 needs admin; re-launch elevated if necessary
    if needs_admin_for_port(port):
        browser_flag = "--open-browser" if open_browser else "--no-browser"
        headless_flag = " --headless" if HEADLESS else ""
        _log(f"Port {port} needs admin — requesting UAC elevation")
        if relaunch_elevated(f"--run {browser_flag}{headless_flag}", show_cmd=0 if HEADLESS else 2):
            # Parent exits; elevated child serves
            if not HEADLESS:
                info("Approved UAC — server starting with admin (port 80)...")
                time.sleep(2)
            return
        if not HEADLESS:
            info("UAC declined — falling back to a non-privileged port")
        port = choose_listen_port() if port_in_use(BIND_HOST, PORT) else FALLBACK_PORT
        if port == 80:
            port = find_free_port(FALLBACK_PORT)
        write_local_env(port)

    url = public_url(port)
    bind = f"{BIND_HOST}:{port}"
    _log(f"Starting server on {url} (bind {bind})")

    if not HEADLESS:
        banner("SERVER READY")
        print(f"  URL:      {url}")
        print(f"  Bind:     {bind}")
        print(f"  Admin:    {ADMIN_EMAIL}")
        print(f"  Password: {ADMIN_PASSWORD}")
        print()
        print("  This window can stay minimized.")
        print("  Auto-start service is registered for system reboot.")
        print("  Press Ctrl+C to stop, or use STOP.bat")
        print("=" * 64)
        print()

    if open_browser:
        open_browser_later(url)

    env = os.environ.copy()
    env["PATH"] = str(PHP_DIR) + os.pathsep + env.get("PATH", "")
    env["PHPRC"] = str(RUNTIME_DIR)

    log_fh = LOG_FILE.open("a", encoding="utf-8")
    log_fh.write(f"\n----- server start {time.strftime('%Y-%m-%d %H:%M:%S')} url={url} bind={bind} -----\n")
    log_fh.flush()

    cmd = php_cmd("artisan", "serve", f"--host={BIND_HOST}", f"--port={str(port)}")
    proc = None
    try:
        proc = subprocess.Popen(
            cmd,
            cwd=str(APP_DIR),
            env=env,
            stdout=log_fh,
            stderr=subprocess.STDOUT,
        )
        write_pid(proc.pid, port)
        _log(f"PHP artisan serve PID={proc.pid}")
        proc.wait()
    except KeyboardInterrupt:
        if not HEADLESS:
            print("\n  Stopping server...")
        if proc:
            try:
                proc.terminate()
                proc.wait(timeout=5)
            except Exception:
                try:
                    proc.kill()
                except Exception:
                    pass
        if not HEADLESS:
            print("  Server stopped.")
    except Exception as exc:
        _log(f"artisan serve failed: {exc}")
        if not HEADLESS:
            err(f"artisan serve failed ({exc}), trying PHP built-in server...")
        router = APP_DIR / "server.php"
        if not router.exists():
            router.write_text(
                """<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}
require_once __DIR__.'/public/index.php';
""",
                encoding="utf-8",
            )
        cmd = php_cmd("-S", f"{BIND_HOST}:{port}", "-t", str(PUBLIC_DIR), str(router))
        try:
            proc = subprocess.Popen(
                cmd,
                cwd=str(APP_DIR),
                env=env,
                stdout=log_fh,
                stderr=subprocess.STDOUT,
            )
            write_pid(proc.pid, port)
            proc.wait()
        except KeyboardInterrupt:
            if proc:
                try:
                    proc.terminate()
                except Exception:
                    pass
        except Exception as exc2:
            die(f"Failed to start server: {exc2}")
    finally:
        clear_pid()
        try:
            log_fh.close()
        except Exception:
            pass


def prepare_app(port: int | None = None) -> int:
    """Common setup before serving. Returns chosen port."""
    ensure_dirs()
    ensure_local_dns()
    write_portable_php_ini()
    check_prerequisites()
    chosen = port if port is not None else choose_listen_port()
    if chosen != PORT and not HEADLESS:
        info(f"Using port {chosen} — open {public_url(chosen)}")
    write_local_env(chosen)
    ensure_sqlite()
    # Composer was installed with --no-scripts (no .env yet); discover packages now
    try:
        run_php(["artisan", "package:discover", "--ansi"], check=False, capture=True)
    except Exception:
        pass
    first_time_setup()
    return chosen


# ---------------------------------------------------------------------------
# Windows auto-start "service" (Scheduled Task)
# ---------------------------------------------------------------------------
def _vbs_escape(path: str) -> str:
    return path.replace("\\", "\\\\").replace('"', '""')


def write_service_vbs() -> None:
    """VBS launcher: runs server hidden for auto-start service after reboot."""
    py = _vbs_escape(find_python_command()[0])
    script = _vbs_escape(str(ROOT / "local_server.py"))
    root = _vbs_escape(str(ROOT))
    content = f'''Option Explicit
Dim sh, cmd
Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "{root}"
cmd = """{py}"" ""{script}"" --run --no-browser --headless"
' 0 = hidden window (auto-start service)
sh.Run cmd, 0, False
'''
    SERVICE_VBS.write_text(content, encoding="utf-8")


def write_minimized_vbs(open_browser: bool = True) -> Path:
    """Create a one-shot VBS that starts the server minimized."""
    py = _vbs_escape(find_python_command()[0])
    script = _vbs_escape(str(ROOT / "local_server.py"))
    root = _vbs_escape(str(ROOT))
    browser_flag = "--open-browser" if open_browser else "--no-browser"
    path = RUNTIME_DIR / "start_minimized.vbs"
    content = f'''Option Explicit
Dim sh, cmd
Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "{root}"
cmd = """{py}"" ""{script}"" --run {browser_flag}"
' 2 = minimized window
sh.Run cmd, 2, False
'''
    path.write_text(content, encoding="utf-8")
    return path


def task_exists() -> bool:
    result = subprocess.run(
        ["schtasks", "/Query", "/TN", TASK_NAME],
        capture_output=True,
        text=True,
    )
    return result.returncode == 0


def install_autostart_service() -> bool:
    """
    Register Windows auto-start service (Scheduled Task).
    Returns True if installed (or already installed).
    """
    write_service_vbs()
    if task_exists():
        if not HEADLESS:
            ok(f"Auto-start service already installed: {TASK_NAME}")
        SERVICE_MARKER.write_text("installed\n", encoding="utf-8")
        return True

    # Always install a reliable user logon task first (works with user-installed Python).
    logon_ok = create_startup_task(mode="onlogon")
    if logon_ok and not HEADLESS:
        ok("Auto-start registered: starts after reboot when you sign in")

    # If admin (or user accepts UAC), also try true boot-time task.
    if is_admin():
        boot_ok = create_startup_task(mode="onstart")
        if boot_ok and not HEADLESS:
            ok("Also registered boot-time system start (ONSTART)")
        return logon_ok or boot_ok

    if not HEADLESS:
        info("Optional: approve UAC to also enable start at system boot...")
    try:
        rc = ctypes.windll.shell32.ShellExecuteW(
            None,
            "runas",
            sys.executable,
            f'"{ROOT / "local_server.py"}" --install-service-only',
            str(ROOT),
            1,
        )
        if rc > 32:
            for _ in range(20):
                time.sleep(0.3)
                if SERVICE_MARKER.exists() and "onstart" in SERVICE_MARKER.read_text(encoding="utf-8", errors="ignore"):
                    if not HEADLESS:
                        ok("Boot-time system start enabled")
                    break
        elif not HEADLESS:
            info("UAC skipped — logon auto-start is still active")
    except Exception as exc:
        _log(f"Elevation optional step failed: {exc}")

    return logon_ok


def create_startup_task(mode: str = "onlogon") -> bool:
    """
    Create scheduled task.
    mode='onlogon'  -> at user sign-in after reboot (reliable, no admin)
    mode='onstart'  -> at system boot (admin; uses current user when possible)
    """
    write_service_vbs()
    tr = f'wscript.exe "{SERVICE_VBS}"'

    if mode == "onstart":
        # Boot-time task as current user (keeps access to user Python install).
        user = os.environ.get("USERNAME") or os.environ.get("USER")
        domain = os.environ.get("USERDOMAIN", "")
        run_as = f"{domain}\\{user}" if domain and user else (user or "")
        cmd = [
            "schtasks", "/Create",
            "/TN", TASK_NAME + "Boot",
            "/TR", tr,
            "/SC", "ONSTART",
            "/RL", "HIGHEST",
            "/F",
            "/NP",
        ]
        if run_as:
            cmd.extend(["/RU", run_as])
        result = subprocess.run(cmd, capture_output=True, text=True)
        if result.returncode != 0:
            # Last resort: SYSTEM (only works if Python is machine-wide)
            cmd = [
                "schtasks", "/Create",
                "/TN", TASK_NAME + "Boot",
                "/TR", tr,
                "/SC", "ONSTART",
                "/RL", "HIGHEST",
                "/F",
                "/RU", "SYSTEM",
            ]
            result = subprocess.run(cmd, capture_output=True, text=True)
            if result.returncode != 0:
                _log(f"ONSTART task failed: {result.stdout} {result.stderr}")
                return False
            SERVICE_MARKER.write_text("onstart-system\n", encoding="utf-8")
            _log("Installed ONSTART task as SYSTEM")
            return True
        prev = SERVICE_MARKER.read_text(encoding="utf-8") if SERVICE_MARKER.exists() else ""
        SERVICE_MARKER.write_text((prev + "\nonstart\n").strip() + "\n", encoding="utf-8")
        _log("Installed ONSTART task for current user")
        return True

    # At user logon — works without admin for current user
    cmd = [
        "schtasks", "/Create",
        "/TN", TASK_NAME,
        "/TR", tr,
        "/SC", "ONLOGON",
        "/RL", "HIGHEST",
        "/F",
    ]
    result = subprocess.run(cmd, capture_output=True, text=True)
    if result.returncode != 0:
        err(f"Failed to create auto-start task: {(result.stderr or result.stdout or '').strip()}")
        _log(f"ONLOGON task failed: {result.stdout} {result.stderr}")
        return False
    SERVICE_MARKER.write_text("onlogon\n", encoding="utf-8")
    _log("Installed ONLOGON task")
    return True


def uninstall_autostart_service() -> None:
    removed_any = False
    for name in (TASK_NAME, TASK_NAME + "Boot"):
        result = subprocess.run(
            ["schtasks", "/Query", "/TN", name],
            capture_output=True,
            text=True,
        )
        if result.returncode != 0:
            continue
        delete = subprocess.run(
            ["schtasks", "/Delete", "/TN", name, "/F"],
            capture_output=True,
            text=True,
        )
        if delete.returncode == 0:
            ok(f"Removed auto-start task: {name}")
            removed_any = True
        else:
            err(f"Could not remove {name}: {(delete.stderr or delete.stdout or '').strip()}")
            if not is_admin():
                info("If removal fails, re-run UNINSTALL_SERVICE.bat and approve UAC")

    if not removed_any:
        info("No auto-start service tasks were found.")

    if SERVICE_MARKER.exists():
        try:
            SERVICE_MARKER.unlink()
        except Exception:
            pass
    if SERVICE_VBS.exists():
        try:
            SERVICE_VBS.unlink()
        except Exception:
            pass


def start_minimized(open_browser: bool = True) -> None:
    """Launch the server in a minimized console and return immediately."""
    browser_flag = "--open-browser" if open_browser else "--no-browser"

    # Port 80 needs an elevated process — launch via UAC when not already admin
    if needs_admin_for_port(PORT):
        info("Port 80 needs admin once so the URL has no :port number...")
        if relaunch_elevated(f"--run {browser_flag}", show_cmd=2):
            for _ in range(90):
                if get_running_port():
                    return
                time.sleep(0.5)
            return
        info("UAC declined — starting without elevation (fallback port if needed)")

    vbs = write_minimized_vbs(open_browser=open_browser)
    subprocess.Popen(
        ["wscript.exe", str(vbs)],
        cwd=str(ROOT),
        close_fds=True,
    )
    # Wait until port is up (or timeout)
    for _ in range(60):
        port = get_running_port()
        if port:
            return
        time.sleep(0.5)


def cmd_install_and_start(open_browser: bool = True) -> None:
    """START.bat entry: install auto-start service + start minimized."""
    global HEADLESS
    banner("Visitor Pass - Install & Start")
    print("  Installing auto-start service + starting minimized server")
    print(f"  Local address: {public_url(PORT)}")
    print()

    ensure_dirs()
    ensure_local_dns()
    installed = install_autostart_service()
    if installed:
        ok("Will start automatically after system restart / logon")
    else:
        err("Auto-start service could not be installed (server will still start now)")

    running_port = get_running_port()
    if running_port:
        # Migrate old :8000 instance to clean :80 URL when possible
        if running_port != PORT and not port_in_use(BIND_HOST, PORT):
            info(f"Stopping old server on port {running_port} to switch to port {PORT}...")
            stop_server_processes()
            running_port = None
        else:
            url = public_url(running_port)
            ok(f"Server already running at {url}")
            if open_browser:
                webbrowser.open(url)
            banner("READY")
            print(f"  URL:      {url}")
            print(f"  Admin:    {ADMIN_EMAIL}")
            print(f"  Password: {ADMIN_PASSWORD}")
            print()
            print("  Console is minimized / background.")
            print("  Auto-start service: ON" if installed else "  Auto-start service: OFF")
            print("  Use STOP.bat to stop, UNINSTALL_SERVICE.bat to remove auto-start.")
            print("=" * 64)
            if not HEADLESS:
                time.sleep(4)
            return

    # Do first-time setup in this visible window so errors are shown
    try:
        prepare_app(choose_listen_port())
    except SystemExit:
        raise
    except Exception as exc:
        die(f"Setup failed: {exc}")

    info("Starting server minimized...")
    start_minimized(open_browser=open_browser)

    port = get_running_port() or PORT
    # Give it a moment
    time.sleep(1.5)
    port = get_running_port() or port

    banner("READY")
    print(f"  URL:      {public_url(port)}")
    print(f"  Admin:    {ADMIN_EMAIL}")
    print(f"  Password: {ADMIN_PASSWORD}")
    print()
    print("  Server console is running MINIMIZED.")
    print("  Auto-start after reboot: " + ("YES" if installed else "NO"))
    print("  Logs: runtime\\server.log")
    print("  Stop: STOP.bat")
    print("  Remove auto-start: UNINSTALL_SERVICE.bat")
    print("=" * 64)
    print()
    print("  This window will close in 5 seconds...")
    time.sleep(5)


def cmd_run(open_browser: bool = True) -> None:
    """Long-running server process (minimized console or hidden service)."""
    ensure_local_dns()
    running = get_running_port()
    if running:
        url = public_url(running)
        _log(f"Already running on {url}")
        if open_browser:
            webbrowser.open(url)
        if not HEADLESS:
            ok(f"Already running at {url}")
            time.sleep(2)
        return

    port = prepare_app()
    # Prefer elevating so we can stay on port 80 (clean URL)
    if needs_admin_for_port(port):
        browser_flag = "--open-browser" if open_browser else "--no-browser"
        headless_flag = " --headless" if HEADLESS else ""
        if relaunch_elevated(f"--run {browser_flag}{headless_flag}", show_cmd=0 if HEADLESS else 2):
            return
        # UAC declined: reconfigure for fallback port
        if not HEADLESS:
            info("UAC declined — using fallback port (URL will include :port)")
        port = find_free_port(FALLBACK_PORT)
        write_local_env(port)
    start_server(port, open_browser=open_browser)


def cmd_stop() -> None:
    banner("Stopping Visitor Pass Local Server")
    n = stop_server_processes()
    if n:
        ok(f"Stopped {n} process(es)")
    else:
        info("No running server process found (ports cleared if any)")
    print()


def parse_args() -> argparse.Namespace:
    p = argparse.ArgumentParser(description="Visitor Pass local host / auto-start service")
    p.add_argument(
        "--install-and-start",
        action="store_true",
        help="Install auto-start service and start server minimized (START.bat)",
    )
    p.add_argument(
        "--install-service-only",
        action="store_true",
        help="Only install the Windows auto-start scheduled task (elevated)",
    )
    p.add_argument(
        "--ensure-hosts",
        action="store_true",
        help="Elevated helper: add visitorpass.localhost to Windows hosts file",
    )
    p.add_argument(
        "--uninstall-service",
        action="store_true",
        help="Remove auto-start service and stop server",
    )
    p.add_argument(
        "--run",
        action="store_true",
        help="Run the server in this process (used by minimized/service launcher)",
    )
    p.add_argument("--stop", action="store_true", help="Stop the local server")
    p.add_argument("--open-browser", action="store_true", help="Open browser after start")
    p.add_argument("--no-browser", action="store_true", help="Do not open browser")
    p.add_argument("--headless", action="store_true", help="No interactive prompts")
    return p.parse_args()


def main() -> None:
    global HEADLESS
    args = parse_args()
    HEADLESS = bool(args.headless)

    open_browser = True
    if args.no_browser:
        open_browser = False
    elif args.open_browser:
        open_browser = True
    elif args.headless:
        open_browser = False

    try:
        if args.uninstall_service:
            cmd_stop()
            uninstall_autostart_service()
            if not HEADLESS:
                print("Done.")
                time.sleep(2)
            return

        if args.stop:
            cmd_stop()
            if not HEADLESS:
                time.sleep(1)
            return

        if args.ensure_hosts:
            # Elevated helper: register local DNS name in hosts file
            ok_write = write_hosts_entry()
            if ok_write:
                print(f"OK: {LOCAL_DNS} -> 127.0.0.1")
            else:
                print(f"FAILED: could not write hosts entry for {LOCAL_DNS}")
                sys.exit(1)
            time.sleep(0.5)
            return

        if args.install_service_only:
            # Elevated helper: install boot-time task (+ ensure logon task)
            ensure_dirs()
            write_hosts_entry()  # already elevated — also ensure local DNS
            write_service_vbs()
            create_startup_task(mode="onlogon")
            create_startup_task(mode="onstart")
            if not HEADLESS:
                print("Service install finished.")
                time.sleep(1)
            return

        if args.install_and_start or (not args.run and len(sys.argv) == 1):
            # Default action for START.bat / double-click
            cmd_install_and_start(open_browser=True)
            return

        if args.run:
            cmd_run(open_browser=open_browser)
            return

        # Fallback
        cmd_install_and_start(open_browser=open_browser)

    except KeyboardInterrupt:
        print("\n  Cancelled.")
        sys.exit(0)
    except SystemExit:
        raise
    except Exception as exc:
        die(f"Unexpected error: {exc}")


if __name__ == "__main__":
    main()
