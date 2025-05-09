<?php

namespace App\Http\Controllers;

use App\Services\TranslationService;
use App\Traits\ApiResponse;
use App\Traits\LoadsRelations;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests,ApiResponse,LoadsRelations;


    protected TranslationService $translator;

    public function __construct(TranslationService $translator)
    {
        $this->translator = $translator;
    }


    public function uploadImage(Request $request, $sub_directory)
    {

        if ($request->hasFile('image')) {
            return  $request->getSchemeAndHttpHost() . '/storage/' .$request->file('image')->store("images/$sub_directory", 'public');

        }

    }

}
