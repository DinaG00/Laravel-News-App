<x-app-layout>
    <div class="newspaper-bg">
        <div class="newspaper-container">
            <div id="news-vue-app" data-auth="{{ auth()->check() ? '1' : '0' }}" data-user-name="{{ auth()->check() ? auth()->user()->name : '' }}"></div>
        </div>
    </div>
</x-app-layout>
