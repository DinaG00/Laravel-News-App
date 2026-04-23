<x-app-layout>
    <x-slot name="header">
        <h2 class="dashboard-title">
            {{ __('Your Dashboard') }}
        </h2>
    </x-slot>

    <div class="dashboard-container">
        <div class="dashboard-inner">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="{{ route('news') }}" class="breadcrumb-link">FinPAPER</a>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current">Dashboard</span>
            </nav>

            <div class="dashboard-content">
                <!-- Saved News Section -->
                <div id="saved-news-app" data-auth="1" data-user-name="{{ Auth::user()?->name ?? '' }}"></div>

                <!-- Saved Markets Section -->
                <div id="saved-markets-app" data-auth="1" data-user-name="{{ Auth::user()?->name ?? '' }}"></div>
            </div>
        </div>
    </div>
</x-app-layout>
