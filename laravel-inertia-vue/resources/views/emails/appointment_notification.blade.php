    <!doctype html>
    <html>
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background:#f7fafc; color:#1f2937; }
        .card { max-width:640px; margin:32px auto; background:white; border-radius:8px; overflow:hidden; box-shadow:0 4px 18px rgba(0,0,0,0.08); }
        .header { padding:20px; display:flex; align-items:center; gap:12px; background:#0f172a; color:white }
        .logo { width:64px; height:64px; object-fit:contain; border-radius:8px; background:white; padding:4px }
        .body { padding:24px; }
        .btn { display:inline-block; padding:10px 16px; background:#2563eb; color:white; border-radius:6px; text-decoration:none }
        .muted { color:#6b7280; font-size:14px }
    </style>
    </head>
    <body>
    <div class="card">
        <div class="header">
        <img class="logo" src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTAFfG-a7rTko_prForzsEBBZDodHz_wDSR_w&s" alt="Clinica Logo">
        <div>
            <div style="font-weight:700; font-size:18px">Clínica Local SAS</div>
            <div style="font-size:13px; opacity:0.85">Notificación de cita médica</div>
        </div>
        </div>

        <div class="body">
        <p>Hola {{ $appointment->patient->name ?? 'Paciente' }},</p>

        <p class="muted">Este mensaje es para informarle sobre el estado de su solicitud de cita.</p>

        <ul>
            <li><strong>Fecha:</strong> {{ $appointment->date }}</li>
            <li><strong>Hora:</strong> {{ $appointment->time }}</li>
            <li><strong>Especialista:</strong> {{ $appointment->doctor->name ?? 'N/A' }}</li>
            <?php
            $s = strtolower((string) $status);
            $label = match ($s) {
                'pending', 'pendiente', 'pendiente de aprobacion', 'pendiente de aprobación' => 'Pendiente de aprobación',
                'confirmed', 'confirmada' => 'Confirmada',
                'approved', 'aceptada' => 'Aceptada',
                'rejected', 'rechazada' => 'Rechazada',
                'completed', 'completada' => 'Completada',
                default => ucfirst($status),
            };
            ?>
            <li><strong>Estado:</strong> {{ $label }}</li>
        </ul>

        @if(!empty($appointment->notes))
        <p><strong>Notas:</strong> {{ $appointment->notes }}</p>
        @endif
        <p class="muted" style="margin-top:18px">Gracias por confiar en Clínica Local SAS.</p>
        </div>
    </div>
    </body>
    </html>
