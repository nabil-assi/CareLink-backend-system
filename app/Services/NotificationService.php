<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\GeneralNotificationMail; // سننشئ هذا الكلاس الموحد

class NotificationService
{
    public static function send($type, $user, $data = [])
    {
        $subject = self::getSubject($type);
        $template = self::getTemplate($type);

        Mail::to($user->email)->send(new GeneralNotificationMail($subject, $template, $data));
    }

    private static function getSubject($type)
    {
        return match($type) {
            'doctor_approved' => 'تم تفعيل حسابك في CareLink',
            'doctor_rejected' => 'تحديث بخصوص طلب انضمامك',
            'password_reset'  => 'إعادة تعيين كلمة السر',
            default           => 'إشعار من CareLink',
        };
    }

    private static function getTemplate($type)
    {
        return match($type) {
            'doctor_approved' => 'emails.doctors.approved',
            'doctor_rejected' => 'emails.doctors.rejected',
            'password_reset' => 'emails.auth.password_reset',
            default           => 'emails.general',
        };
    }
}