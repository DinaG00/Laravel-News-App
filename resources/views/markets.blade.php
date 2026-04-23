<x-app-layout>
    <div id="markets-vue-app" data-auth="{{ auth()->check() ? 1 : 0 }}" data-user-name="{{ Auth::user()?->name ?? '' }}"></div>
</x-app-layout>
