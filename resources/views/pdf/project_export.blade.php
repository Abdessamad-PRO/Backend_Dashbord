<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Projet: {{ $project->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        .project-info {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
        }
        .section-title {
            background-color: #4a5568;
            color: white;
            padding: 8px;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            padding: 8px;
            text-align: left;
        }
        td {
            padding: 8px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Détails du Projet</h1>
        <p>Généré le {{ date('d/m/Y') }}</p>
    </div>

    <div class="project-info">
        <h2>{{ $project->name }}</h2>
        <p><strong>Description:</strong> {{ $project->description }}</p>
        <p><strong>Date de début:</strong> {{ $project->start_date->format('d/m/Y') }}</p>
        <p><strong>Date de fin:</strong> {{ $project->end_date->format('d/m/Y') }}</p>
        <p><strong>Statut:</strong> {{ $project->status }}</p>
        <p><strong>Manager:</strong> {{ $project->manager->name }}</p>
    </div>

    <div class="section-title">
        <h3>Employés assignés au projet</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Email</th>
                <th>Département</th>
                <th>Téléphone</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->departement }}</td>
                <td>{{ $user->telephone }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">
        <h3>Tâches du projet</h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Nom de la tâche</th>
                <th>Description</th>
                <th>Assignée à</th>
                <th>Date de début</th>
                <th>Date de fin</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tasks as $task)
            <tr>
                <td>{{ $task->name }}</td>
                <td>{{ $task->description }}</td>
                <td>{{ $task->assignedUser ? $task->assignedUser->name : 'Non assignée' }}</td>
                <td>{{ $task->start_date->format('d/m/Y') }}</td>
                <td>{{ $task->end_date->format('d/m/Y') }}</td>
                <td>{{ $task->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Ce document est confidentiel et destiné uniquement à l'usage interne.</p>
    </div>
</body>
</html>
