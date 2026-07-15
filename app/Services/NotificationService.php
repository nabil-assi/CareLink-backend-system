<?php

namespace App\Services;

use App\Mail\GeneralNotificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
  public static function sendToAll($title, $body)
{
    User::chunk(100, function ($users) use ($title, $body) {
        foreach ($users as $user) {
            // هنا الخطأ: كنت تمرر $body كقالب
            // التصحيح: نمرر اسم القالب، ونرسل الـ body ضمن البيانات
            Mail::to($user->email)->send(new GeneralNotificationMail(
                $title, 
                'emails.general', // اسم الملف
                ['body' => $body] // البيانات
            ));
        }
    });
}

public static function sendToUser($userId, $modelClass, $title, $body)
{
    $user = User::find($userId);
    if ($user) {
        Mail::to($user->email)->send(new GeneralNotificationMail(
            $title, 
            'emails.general', // اسم الملف
            ['body' => $body] // البيانات
        ));
    }
}
    // الدالة الأصلية لإشعارات النظام
    public static function send($type, User $user, $data = [])
    {
        $subject = self::getSubject($type);
        $template = self::getTemplate($type);

        Mail::to($user->email)->send(new GeneralNotificationMail($subject, $template, $data));
    }

    private static function getSubject($type)
    {
        return match ($type) {
            'doctor_approved' => 'تم تفعيل حسابك في CareLink',
            'doctor_rejected' => 'تحديث بخصوص طلب انضمامك',
            'password_reset' => 'إعادة تعيين كلمة السر',
            default => 'إشعار من CareLink',
        };
    }

    private static function getTemplate($type)
    {
        return match ($type) {
            'doctor_approved' => 'emails.doctors.approved',
            'doctor_rejected' => 'emails.doctors.rejected',
            'password_reset' => 'emails.auth.password_reset',
            default => 'emails.general',
        };
    }
}