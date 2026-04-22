<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .page-container {
            max-width: 960px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .page-heading {
            font-size: 2.4rem;
            font-weight: 800;
            margin-bottom: 16px;
            color: #111827;
        }
    </style>
</head>
<body>
    <main class="page-container">
        <h1 class="page-heading">Latest News</h1>
        <div id="news-vue-app"></div>
    </main>
</body>
</html>