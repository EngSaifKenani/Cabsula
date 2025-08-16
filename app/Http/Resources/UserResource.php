<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * تحويل الريسورس إلى مصفوفة.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // نختار فقط البيانات التي نريد إظهارها في الـ API
        // هذا يساعد على إخفاء البيانات الحساسة مثل كلمة المرور
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'phone_number' => $this->phone_number,
            // يمكنك إضافة رابط الصورة إذا كنت تستخدم Laravel Storage
             'image' => $this->image ? Storage::url($this->image) : null,
        ];
    }
}
