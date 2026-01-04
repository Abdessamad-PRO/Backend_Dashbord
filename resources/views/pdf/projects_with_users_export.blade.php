<?php
use Illuminate\Support\Facades\Date;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Liste des Projets et Employés</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
        }
        .header h2 {
            font-size: 18px;
            color: #666;
        }
        .project {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .project h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .project ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .project li {
            margin: 5px 0;
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 3px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Liste des Projets et Employés</h1>
        <h2>Exporté le {{ Date::now()->format('d/m/Y H:i') }}</h2>
    </div>

    @foreach($projects as $project)
        <div class="project">
            <h2>{{ $project->name }}</h2>
            <ul>
                @foreach($project->users as $user)
                    <li>{{ $user->name }} ({{ $user->email }})</li>
                @endforeach
            </ul>
        </div>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
