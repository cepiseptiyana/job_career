<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;
use App\Models\User;

use GuzzleHttp\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AccountController extends Controller
{
    // this method will show user register page
    public function register()
    {
        return view("front.account.register");
    }

    // this method will save user + cek csrf
    public function registerValidation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:confirm_password',
            'confirm_password' => 'required',
        ]);

        // cek validasi sudah terpenuhi ?
        if ($validator->passes()) {
            // Model users
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            session()->flash('success', 'You have register successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    // this method will show user login page
    public function login()
    {
        return view("front.account.login");
    }

    // this method will show user login page + cek csrf
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->passes()) {
            // Auth = jika benar data user di simpan di session
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return redirect()->route('account.profile');
            } else {
                // with == flash session
                return redirect()->route('account.login')->with('error', 'Either Email/Password is incorrect.');
            }
        } else {
            // validator tidak terpenuhi
            return redirect()->route('account.login')->withErrors($validator)->withInput($request->only('email'));
        }
    }

    // PROFILE
    public function profile()
    {
        $id = Auth::user()->id;
        $user = User::where('id', $id)->first();
        // $user = User::find('id');
        return view('front.account.profile', ['user' => $user]);
    }

    // UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:5|max:20',
            'email' => 'required|email|unique:users,email,' . $id . ',id',

        ]);

        if ($validator->passes()) {
            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->designation = $request->designation;
            $user->save(); // save to models user

            session()->flash('success', 'Profile updated successfully.');

            return response()->json(['status' => true, 'errors' => []]);
        } else {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }
    }

    // UPDATE-PICTURE
    // public function updateProfilePic(Request $request)
    // {
    //     // seperti var_dump
    //     // dd($request->all());

    //     $id = Auth::user()->id;

    //     $validator = Validator::make($request->all(), [
    //         'image' => 'required|image'
    //     ]);

    //     if ($validator->passes()) {
    //         $image = $request->image;
    //         $ext = $image->getClientOriginalExtension();
    //         $imageName = $id . '-' . time() . '.' . $ext;
    //         $image->move(public_path('/profile_pic/'), $imageName);

    //         // create small thumbnail
    //         $sourcePath = public_path('/profile_pic/' . $imageName); // get path image 
    //         $manager = new ImageManager(Driver::class);
    //         $image = $manager->read($sourcePath);

    //         // crop the image lalu simpan di folder thumb 
    //         $image->cover(150, 150);
    //         $image->toPng()->save(public_path('/profile_pic/thumb/' . $imageName));

    //         // Delete picture lama setiap update picture 
    //         // jangan menggunakan $imagename karena itu image baru setiap input  
    //         // gunakan Auth::user()->image ini image yang digunakan saat ini
    //         File::delete(public_path('/profile_pic/thumb/' . Auth::user()->image));
    //         File::delete(public_path('/profile_pic/' . Auth::user()->image));

    //         User::where('id', $id)->update(['image' => $imageName]);

    //         session()->flash('success', 'Profile Picture Updated Successfully.');

    //         return response()->json(['status' => true, 'errors' => []]);
    //     } else {
    //         return response()->json(['status' => false, 'errors' => $validator->errors()]);
    //     }
    // }

    public function updateProfilePic(Request $request)
    {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(), [
            'image' => 'required|image'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }

        $image = $request->image;
        $ext = $image->getClientOriginalExtension();
        $fileName = $id . '-' . time() . '.' . $ext;

        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $bucket = env('SUPABASE_BUCKET', 'profile-pics');
        $serviceRoleKey = env('SERVICE_ROLE_KEY');

        $client = new Client();

        $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fileName}";

        try {
            $resp = $client->post($uploadUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$serviceRoleKey}",
                    'Content-Type'  => $image->getMimeType(), // e.g. image/jpeg
                ],
                'body' => fopen($image->getPathname(), 'r'),
                'verify' => false,
                // timeout optional:
                'timeout' => 30,
            ]);
        } catch (\Exception $e) {
            // tangani error (mis. 4xx/5xx)
            return response()->json(['error' => 'upload_failed', 'msg' => $e->getMessage()], 500);
        }


        User::where('id', $id)->update(['image' => $fileName]);
        session()->flash('success', 'Profile Picture Updated Successfully.');
        return response()->json(['status' => true, 'errors' => []]);
    }

    // CREATE JOB
    public function createJob()
    {
        $categories = Category::orderBy('name', 'ASC')->where('status', 1)->get();

        $jobTypes = JobType::orderBy('name', 'ASC')->where('status', 1)->get();


        return view('front.account.job.create', ['categories' => $categories, 'jobTypes' => $jobTypes,]);
    }

    // MY JOB
    public function myJobs()
    {
        $jobs = Job::where('user_id', Auth::user()->id)->with('jobType', 'applications')->orderBy('created_at', 'DESC')->paginate(10);
        // dd($jobs);

        return view('front.account.job.my-jobs', ['jobs' => $jobs]);
    }

    // SAVE JOB
    public function saveJob(Request $request)
    {
        $user_id = Auth::user()->id;

        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'jobType' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required',
            'description' => 'required',
            'company_name' => 'required|min:3|max:75',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            $job = new Job();
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = $user_id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;
            $job->save();

            session()->flash('success', 'Job Added Successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    // EDIT JOB 
    public function editJob(Request $request, $id)
    {


        $categories = Category::orderBy('name', 'ASC')->where('status', 1)->get();
        $jobTypes = JobType::orderBy('name', 'ASC')->where('status', 1)->get();

        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $id,
        ])->first();

        if ($job == null) {
            abort(404);
        }

        return view('front.account.job.edit', ['categories' => $categories, 'jobTypes' => $jobTypes, 'job' => $job]);
    }

    // UPDATE JOB
    public function updateJob(Request $request, $id)
    {
        $user_id = Auth::user()->id;

        $rules = [
            'title' => 'required|min:5|max:200',
            'category' => 'required',
            'jobType' => 'required',
            'vacancy' => 'required|integer',
            'location' => 'required',
            'description' => 'required',
            'company_name' => 'required|min:3|max:75',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->passes()) {
            $job = Job::find($id);
            $job->title = $request->title;
            $job->category_id = $request->category;
            $job->job_type_id = $request->jobType;
            $job->user_id = $user_id;
            $job->vacancy = $request->vacancy;
            $job->salary = $request->salary;
            $job->location = $request->location;
            $job->description = $request->description;
            $job->benefits = $request->benefits;
            $job->responsibility = $request->responsibility;
            $job->qualifications = $request->qualifications;
            $job->keywords = $request->keywords;
            $job->experience = $request->experience;
            $job->company_name = $request->company_name;
            $job->company_location = $request->company_location;
            $job->company_website = $request->company_website;
            $job->save();

            session()->flash('success', 'Job Update Successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    // DELETE JOB
    public function deleteJob(Request $request)
    {
        $job = Job::where([
            'user_id' => Auth::user()->id,
            'id' => $request->jobId
        ])->first();

        if ($job == null) {
            session()->flash('error', 'Either Job Deleted or Not Found.');
            return response()->json([
                'status' => true,
            ]);
        }

        Job::where('id', $request->jobId)->delete();
        session()->flash('success', 'Job Deleted Successfully.');
        return response()->json([
            'status' => true,
        ]);
    }

    // MY JOB APPLICAITON
    public function myJobApplications()
    {
        $jobApplications = JobApplication::where('user_id', Auth::user()->id)
            ->with(['job', 'job.jobType', 'job.applications'])
            ->orderBy('created_at', 'DESC')
            ->paginate(10);


        return view('front.account.job.my-job-applications', ['jobApplications' => $jobApplications]);
    }

    // REMOVE JOB APPLICATION
    public function removeJobs(Request $request)
    {
        $jobApplication = JobApplication::where(['id' => $request->id, 'user_id' => Auth::user()->id])->first();

        if ($jobApplication == null) {
            session()->flash('error', 'Job Application Not Found');
            return response()->json(['status' => false]);
        }

        JobApplication::find($request->id)->delete();

        session()->flash('success', 'Job Application Removed Successfully.');
        return response()->json(['status' => true]);
    }

    // PAGE SAVED JOB ACCOUNT 
    public function savedJob()
    {
        $savedJobs = SavedJob::where([
            'user_id' => Auth::user()->id,
        ])
            ->with(['job', 'job.jobType', 'job.applications'])
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        return view('front.account.job.saved-job', ['savedJobs' => $savedJobs]);
    }

    public function removeSavedJobs(Request $request)
    {
        $savedJobs = SavedJob::where(['id' => $request->id, 'user_id' => Auth::user()->id])->first();

        if ($savedJobs == null) {
            session()->flash('error', 'Saved Job Not Found');
            return response()->json(['status' => false]);
        }

        SavedJob::find($request->id)->delete();

        session()->flash('success', 'Job Saved Removed Successfully.');
        return response()->json(['status' => true]);
    }

    // UPDATE PASSWORD
    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ]);
        }

        // cek old_password jika beda dengan database maka incorrect
        if (Hash::check($request->old_password, Auth::user()->password) == false) {
            session()->flash('error', 'Your Old Password Is Incorrect.');

            return response()->json([
                'status' => true,
            ]);
        }

        // CHANGE PAASSWORD
        $user = User::find(Auth::user()->id);
        $user->password = Hash::make($request->new_password);
        $user->save();

        session()->flash('success', 'Password Updated Successfully.');
        return response()->json([
            'status' => true,
        ]);
    }


    // LOGOUT
    public function logout()
    {
        // Auth logout remove session akun 
        Auth::logout();
        return redirect()->route('account.login');
    }
}
