<?php

namespace Modules\Helpdesk\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.conversations.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'from' => ['nullable', 'string', 'in:auto,af,sq,am,ar,az,be,bn,bs,bg,ca,ceb,ny,zh,co,hr,cs,da,nl,en,eo,et,tl,fi,fr,fy,gl,ka,de,el,gu,ht,ha,haw,iw,hi,hmn,hu,is,ig,id,ga,it,ja,jw,kn,kk,km,ko,ku,ky,lo,la,lv,lt,lb,mk,mg,ms,ml,mt,mi,mr,mn,my,ne,no,ps,fa,pl,pt,pa,ro,ru,sm,gd,sr,st,sn,sd,si,sk,sl,so,es,su,sw,sv,tg,ta,te,th,tr,uk,ur,uz,vi,cy,xh,yi,yo,zu'],
            'to' => ['required', 'string', 'size:2'],
        ];
    }

    public function messages(): array
    {
        return [
            'text.required' => 'El texto a traducir es obligatorio.',
            'text.max' => 'El texto no puede superar los 2000 caracteres.',
            'from.in' => 'El idioma de origen no es válido.',
            'to.required' => 'El idioma de destino es obligatorio.',
            'to.size' => 'El código de idioma destino debe tener 2 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'text' => 'texto',
            'from' => 'idioma origen',
            'to' => 'idioma destino',
        ];
    }
}
