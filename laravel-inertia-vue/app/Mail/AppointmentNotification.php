<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Appointment;

class AppointmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public string $status;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, string $status)
    {
        $this->appointment = $appointment;
        $this->status = $status;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = sprintf('Clínica Local SAS - Solicitud de cita (%s)', ucfirst($this->status));

        return $this->subject($subject)
            ->view('emails.appointment_notification')
            ->with([
                'appointment' => $this->appointment,
                'status' => $this->status,
            ]);
    }
}
