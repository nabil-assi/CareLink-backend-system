<?php

namespace App\Services;

use App\Mail\GeneralNotificationMail;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    // الدالة المطلوبة للكونترولر (إرسال للجميع)
    public static function sendToAll($title, $body)
    {
        // سأقوم بجلب جميع الأطباء والمرضى وإرسال إيميل لهم
        $users = Doctor::all()->merge(Patient::all());

        foreach ($users as $user) {
            Mail::to($user->email)->send(new GeneralNotificationMail($title, $body, []));
        }
    }

    // الدالة المطلوبة للكونترولر (إرسال لمستخدم محدد)
    public static function sendToUser($userId, $userType, $title, $body)
    {
        $model = ($userType === 'doctor') ? Doctor::find($userId) : Patient::find($userId);

        if ($model) {
            Mail::to($model->email)->send(new GeneralNotificationMail($title, $body, []));
        }
    }

    // دالتك الأصلية لإرسال إشعارات النظام (مثل التفعيل والرفض)
    public static function send($type, $user, $data = [])
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
