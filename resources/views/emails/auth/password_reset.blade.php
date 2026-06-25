@component('mail::message')
# طلب إعادة تعيين كلمة السر

لقد تلقينا طلباً لإعادة تعيين كلمة السر الخاصة بحسابك في CareLink.

@component('mail::button', ['url' => 'https://carelink.com/reset-password?token=' . $data['token']])
إعادة تعيين كلمة السر
@endcomponent

إذا لم تطلب هذا، يمكنك تجاهل هذا الإيميل.
@endcomponent