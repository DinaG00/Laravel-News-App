<nav x-data="{ open: false }" class="newspaper-nav">
    <div class="nav-inner">
        <div class="nav-bracket">
            <!-- Logo / Brand -->
            <div class="nav-brand">
                <a href="{{ route('news') }}" class="nav-logo">
                    <span class="nav-logo-text">FP</span>
                    <span class="nav-logo-full">FinPAPER</span>
                </a>
            </div>

            <!-- Desktop Links -->
            <div class="desktop-links">
                <a class="nav-link {{ request()->routeIs('news') ? 'active' : '' }}" href="{{ route('news') }}">
                    {{ __('News') }}
                </a>
                <a class="nav-link {{ request()->routeIs('markets') ? 'active' : '' }}" href="{{ route('markets') }}">
                    {{ __('Markets') }}
                </a>
            </div>
        </div>

        <div class="nav-auth">
            @auth
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    {{ __('Dashboard') }}
                </a>

                <!-- Notification Bell -->
                <div id="notification-bell" data-auth="1"></div>

                <div class="dropdown-wrapper" x-data="{ dropdownOpen: false }" @click.away="dropdownOpen = false">
                    <button @click="dropdownOpen = !dropdownOpen" class="auth-btn">
                        <span>{{ Auth::user()->name }}</span>
                        <svg class="dropdown-chevron" x-bind:class="{ 'rotate': dropdownOpen }" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <div x-show="dropdownOpen" class="dropdown-menu" x-transition>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" class="dropdown-item"
                               onclick="event.preventDefault(); this.closest('form').submit();">Log Out</a>
                        </form>
                    </div>
                </div>
            @else
                <div class="guest-links">
                    <a href="{{ route('login') }}" class="nav-link">Log in</a>
                    <a href="{{ route('register') }}" class="nav-btn">Register</a>
                </div>
            @endauth
        </div>

        <!-- Hamburger -->
        <div class="hamburger">
            <button @click="open = ! open" class="hamburger-btn">
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <line x1="3" y1="12" x2="21" y2="12"/>
                    <line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
                <svg x-show="open" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6L6 18"/>
                    <path d="M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div x-show="open" class="mobile-menu" @click.away="open = false" x-transition>
        <a class="mobile-link {{ request()->routeIs('news') ? 'active' : '' }}" href="{{ route('news') }}">
            {{ __('News') }}
        </a>
        <a class="mobile-link {{ request()->routeIs('markets') ? 'active' : '' }}" href="{{ route('markets') }}">
            {{ __('Markets') }}
        </a>
        @auth
            <a class="mobile-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                {{ __('Dashboard') }}
            </a>
            <a class="mobile-link" href="{{ route('profile.edit') }}">Profile</a>
            <form method="POST" action="{{ route('logout') }}" class="mobile-form">
                @csrf
                <a href="{{ route('logout') }}" class="mobile-link"
                   onclick="event.preventDefault(); this.closest('form').submit();">Log Out</a>
            </form>
        @else
            <a class="mobile-link" href="{{ route('login') }}">Log in</a>
            <a class="mobile-link" href="{{ route('register') }}">Register</a>
        @endauth
    </div>
</nav>
