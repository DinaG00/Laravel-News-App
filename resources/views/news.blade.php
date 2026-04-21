<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News</title>
</head>
<body>
    <h1>Latest News</h1>

    @if(request()->filled('search') || request()->filled('category'))
        <a href="/" 
        style="margin-left:10px; padding:5px 10px; background:#ccc; text-decoration:none; border-radius:5px;">
            Clear filters
        </a>
    @endif

    <form method="GET" action="/">
        <input type="text" name="search" placeholder="Search news..." value="{{ request('search') }}">

        <select name="category">
            <option value="">All</option>

            @foreach(\App\Models\News::select('category')->distinct()->get() as $cat)
                <option value="{{ $cat->category }}"
                    {{ request('category') == $cat->category ? 'selected' : '' }}>
                    {{ $cat->category }}
                </option>
            @endforeach
        </select>

        <button type="submit">Apply</button>
    </form>

    @foreach($news as $item)
    <div style="margin-bottom:20px;">
        <h3>{{ $item->title }}</h3>
        <p>{{ $item->description }}</p>
        <small>{{ $item->source }} | {{ $item->category }}</small>
        <br>
        <a href="{{ $item->url }}" target="_blank">Read more</a>
    </div>
    <hr>
    @endforeach
</body>
</html>