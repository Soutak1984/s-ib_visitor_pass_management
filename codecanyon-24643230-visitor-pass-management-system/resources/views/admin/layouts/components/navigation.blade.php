<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar">
    <div class="form-inline mr-auto">
        <ul class="navbar-nav mr-3">
            <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
        </ul>
    </div>
    <ul class="navbar-nav navbar-right">
        <li class=" hidecheck ">
            <form action="{{ route('admin.visitor.search')}}" method="post">
                {{ csrf_field() }}
                <div class="d-flex form-group  {{ $errors->has('first_name') ? 'has-error' : ''}}" style="margin-bottom: -24px;margin-left:auto">
                    <input class="form-control inputid" style="margin-right: 5px;" type="text" name="visitorID" placeholder="{{ __('topbar_menu.enter_Visitor_id') }}">
                    <button class="btn  d-flex inputbtn align-items-center" type="submit"><i class="fas fa-4x fa-sign-out-alt"></i>{{ __('topbar_menu.check_out') }}</button>
                </div>
            </form>
        </li>
        @if(setting('front_end_enable_disable') == 1)
        <li class="dropdown">
            <a data-toggle="tooltip" data-placement="bottom" title="Go to Frontend" href="{{ route('/') }}" class="nav-link nav-link-lg beep" target="_blank"><i class="fa fa-globe"></i></a>
        </li>
        @endif
        <li class="dropdown">
            <a href="" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                @if(!blank($language))
                @foreach($language as $lang )
                @if(Session()->has('applocale') AND Session()->get('applocale') AND setting('locale'))
                @if(Session()->get('applocale') == $lang->code)
                <div class="d-sm-none d-lg-inline-block "><span class="flag-icon">{{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}</span>{{ $lang->name }}</div>
                @endif
                @else
                @if(setting('locale') == $lang->code)
                <div class="d-sm-none d-lg-inline-block "><span class="flag-icon">{{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }}</span>{{ $lang->name }}</div>
                @endif
                @endif
                @endforeach
                @endif
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                @if(!blank($language))
                @foreach($language as $lang )
                <a href="{{ route('admin.lang.index',$lang->code) }}" class="dropdown-item has-icon">
                    <span class="flag-icon flag-icon-aw">{{ $lang->flag_icon == null ? '🇬🇧' : $lang->flag_icon }} </span>{{ $lang->name }}</a>
                @endforeach
                @endif
            </div>
        </li>


        @if(auth()->user()->myrole == 2)
        @if(!blank($latestVisitors))
        <li class="dropdown custom-visitor-notification">
            <a href="{{ route('admin.profile') }}" data-toggle="dropdown" class="dropdown-toggle custom-notification ">
                <div class=" d-lg-inline-block">
                    <i class="fas fa-bell"></i>
                    <div class="counter badge badge-pill badge-danger text-light">
                        <span class=" ">{{count($latestVisitors)}}</span>
                    </div>
                </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                @foreach($latestVisitors as $visitor)
                <div class="notification-div p-2">
                    <div class="row no-gutters">
                        <div class="col-2">
                            <img src="{{ $visitor->images }}" class="card-img notification-img">
                        </div>
                        <div class="col-10">
                            <div class="pl-2">
                                <p class="visitor-name">{{$visitor->visitor->name}}</p>
                                <p class="visitor-purpose">{{__('Purpose')}} : {{Str::limit($visitor->purpose, 60)}}</p>
                                <a class="btn btn-success btn-sm status-btn" href="{{route('admin.visitor.change-status',[$visitor->id,2,true] )}}">{{__('Accept')}}</a>
                                <a class="btn  btn-danger btn-sm status-btn" href="{{route('admin.visitor.change-status',[$visitor->id,3,true] )}}">{{__('Reject')}}</a>

                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </li>
        @else
        <li class="dropdown custom-visitor-notification">
            <div class=" d-lg-inline-block custom-notification">
                <i class="fas fa-bell"></i>
                <div class="counter badge badge-pill badge-danger text-light">
                    <span class=" ">0</span>
                </div>
            </div>
        </li>
        @endif
        @endif

        <li class="dropdown">
            <a href="{{ route('admin.profile') }}" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
                <img alt="image" src="{{ auth()->user()->images }}" class="rounded-circle mr-1">
                <div class="d-sm-none d-lg-inline-block">{{ __('topbar_menu.hi') }}, {{ auth()->user()->name }}</div>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="{{ route('admin.profile') }}" class="dropdown-item has-icon">
                    <i class="far fa-user"></i> {{ __('topbar_menu.profile') }}
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="dropdown-item has-icon text-danger">
                    <i class="fas fa-sign-out-alt"></i> {{ __('topbar_menu.logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="display-none">
                    @csrf
                </form>
            </div>
        </li>
    </ul>
</nav>