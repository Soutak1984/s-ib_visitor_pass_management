Option Explicit
Dim sh, cmd
Set sh = CreateObject("WScript.Shell")
sh.CurrentDirectory = "C:\\Users\\conta\\Music\\samiran-final-visitor-pass-management-system - Copy"
cmd = """C:\\Users\\conta\\AppData\\Local\\Programs\\Python\\Python312\\python.exe"" ""C:\\Users\\conta\\Music\\samiran-final-visitor-pass-management-system - Copy\\local_server.py"" --run --no-browser --headless"
' 0 = hidden window (auto-start service)
sh.Run cmd, 0, False
