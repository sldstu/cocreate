<div class="flex flex-col items-center justify-center min-h-screen py-2">
    <div class="text-center">
        <h1 class="text-9xl font-bold text-gray-800">404</h1>
        <h2 class="text-6xl font-medium py-8">Page not found</h2>
        <p class="text-2xl pb-8 px-12 font-medium">Oops! The page you are looking for does not exist.</p>
        <a href="?page=<?php echo array_key_first($allowed_pages); ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Go back to dashboard
        </a>
    </div>
</div>
