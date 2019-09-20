<?php

namespace App\Http\Controllers\Api\Company;

use App\Models\Role;
use App\Models\User;
use App\Models\Company;
use App\CompanyRepository;
use App\Models\CompanyType;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Mail\CompanyAdminInvited;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\CompanyAdminInvitation;
use App\Http\Resources\Company as CompanyResource;

class CompaniesController extends Controller
{
    private $companyRepository;

    public function __construct()
    {
        $this->companyRepository = new CompanyRepository;
    }

    public function index(Request $request)
    {
        return CompanyResource::collection($this->companyRepository->getCompanies());
    }

    public function companyList(Request $request)
    {
        $authUser = auth('api')->user();

        $advertiserRole = Role::where('name', 'advertiser')->first();

        if (auth('api')->user()->hasRole('advertiser')) {
            return response()->json([]);
        }

        if (auth('api')->user()->hasRole('admin') || auth('api')->user()->hasRole('user') || auth('api')->user()->hasRole('companyadmin')) {
            return $request->user()->companies->pluck('company_name', 'id');
        }

        if(auth('api')->user()->hasRole('screen_owner')) {
            $allCompanies = [];
            $screenOwnerCompanies = $request->user()->companies->pluck('company_name', 'id')->toArray();
            $users = User::with('companies')->whereHas('creators', function($query) use($authUser) {
                        $query->where('creator_id', $authUser->id);
                    })->whereHas('roles', function($query) use($advertiserRole) {
                        $query->where('role_id', $advertiserRole->id);
                    })->get();

            foreach($users as $user) {
                $allCompanies = array_replace($allCompanies, $user->companies->pluck('company_name', 'id')->toArray());
            }

            return $allCompanies;
        }

        $companies = new Company;
        if ($request->has('type')) {
            if ($request->type == 'screenOwners') {
                $type = [1, 2];
            }

            $companies = $companies::whereIn('company_client_type_id', $type);
        }
        $companies = $companies->pluck('company_name', 'id');

        return response()->json($companies);
    }
}
