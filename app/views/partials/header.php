<?php
/** @var string|null $pageTitle */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> &middot; Reel &mdash; Voice Memo Studio</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Crect width='24' height='24' rx='6' fill='%230B0B10'/%3E%3Cpath d='M9 6a3 3 0 0 1 6 0v6a3 3 0 0 1-6 0z' fill='%238B5CF6'/%3E%3Cpath d='M6 11a6 6 0 0 0 12 0M12 17v3' stroke='%238B5CF6' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    studio: { DEFAULT: '#0B0B10', surface: '#15151D', card: '#1A1A24', inset: '#0E0E14' },
                    line: '#2A2A36',
                    ink: '#EDEBF5',
                    inkmute: '#8E8BA3',
                    violet: { DEFAULT: '#8B5CF6', bright: '#A78BFA', deep: '#5B3FA0', soft: '#211A3B' },
                    rec: '#FB4D4D',
                    mint: '#34D399',
                },
                fontFamily: {
                    display: ['"Space Grotesk"', 'sans-serif'],
                    body: ['"Inter"', 'sans-serif'],
                    mono: ['"JetBrains Mono"', 'monospace'],
                },
            },
        },
    };
</script>
<link href="/assets/css/style.css" rel="stylesheet">
</head>
<body class="font-body bg-studio text-ink min-h-screen">
