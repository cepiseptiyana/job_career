<?php

namespace App\Http\Controllers;

// use App\Mail\JobNotificationEmail;
use App\Models\Category;
use App\Models\User;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobType;
use App\Models\SavedJob;

use GuzzleHttp\Client;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class JobsController extends Controller
{
    // this method will show halaman jobs 
    public function index(Request $request)
    {
        $categories = Category::where('status', 1)->get();
        $jobTypes = JobType::where('status', 1)->get();

        $jobs = Job::where('status', 1);

        // search use keyword
        if (!empty($request->keyword)) {
            $jobs = $jobs->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                $query->orWhere('keywords', 'like', '%' . $request->keyword . '%');
            });
        }

        // search use location
        if (!empty($request->location)) {
            // where = menambah kondisi query AND
            $jobs = $jobs->where('location', $request->location);
        }

        // search use CATEGORY
        if (!empty($request->category)) {
            // where = menambah kondisi query AND
            $jobs = $jobs->where('category_id', $request->category);
        }

        $jobTypeArray = [];
        // search use CATEGORY
        if (!empty($request->jobType)) {
            // [1,2,3]
            $jobTypeArray = explode(',', $request->jobType);

            $jobs = $jobs->whereIn('job_type_id', $jobTypeArray);
        }

        // search use experience
        if (!empty($request->experience)) {
            $jobs = $jobs->where('experience', $request->experience);
        }

        $jobs = $jobs->with(['jobType', 'category']);

        // SORT
        if ($request->sort == '0') {
            $jobs = $jobs->orderBy('created_at', 'ASC');
        } else {
            $jobs = $jobs->orderBy('created_at', 'DESC');
        }

        // paginate
        $jobs = $jobs->paginate(5);

        return view('front.jobs', ['categories' => $categories, 'jobTypes' => $jobTypes, 'jobs' => $jobs, 'jobTypeArray' => $jobTypeArray]);
    }

    // this method will show halaman detail
    public function detail($id)
    {
        // DATA TABLE JOB
        $job = Job::where(['status' => 1, 'id' => $id])->with(['jobType', 'category'])->first();
        // dd($job);

        if ($job == null) {
            abort(404);
        }

        $countSaveJob = 0;

        if (Auth::user()) {
            $countSaveJob = SavedJob::where([
                'user_id' => Auth::user()->id,
                'job_id' => $id,
            ])->count();
        }

        // USER YANG UDAH MELAMAR/APPLY
        $applications = JobApplication::where('job_id', $id)->with('user')->get();
        // dd($applications);

        // dd(Auth::user()->id);

        return view('front.jobDetail', ['job' => $job, 'countSaveJob' => $countSaveJob, 'applications' => $applications]);
    }

    // SAVE JOB
    public function saveJob(Request $request)
    {
        $id = $request->id;

        $job = Job::find($id);

        // Cek apakah Job itu ada di table
        if ($job == null) {
            session()->flash('error', 'Job Not Found.');

            return response()->json([
                'status' => false,
            ]);
        }

        // cek if user already saved job
        $countSaveJob = SavedJob::where([
            'user_id' => Auth::user()->id,
            'job_id' => $id,
        ])->count();

        if ($countSaveJob > 0) {
            session()->flash('error', 'You Already Saved On This Job.');

            return response()->json([
                'status' => false,
            ]);
        }

        // SAVED JOB
        $savedJob = new SavedJob();
        $savedJob->job_id = $id;
        $savedJob->user_id = Auth::user()->id;
        $savedJob->save();


        session()->flash('success', 'You Have Successfully Saved The Job.');

        return response()->json([
            'status' => true,
        ]);
    }

    // APPLY JOB
    public function applyJob(Request $request)
    {
        $job_id = $request->job_id;

        // Validator
        $validator = Validator::make($request->all(), [
            'cv' => 'required|mimes:pdf'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()]);
        }

        // Ambil file
        $cv = $request->file('cv');
        $ext = $cv->getClientOriginalExtension();
        $fileName = $job_id . '-' . time() . '.' . $ext;

        // Supabase info
        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $bucket = env('SUPABASE_BUCKET', 'store_cv');
        $serviceRoleKey = env('SERVICE_ROLE_KEY');

        $uploadUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fileName}";

        $client = new Client();

        try {
            $resp = $client->put($uploadUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$serviceRoleKey}",
                    'Content-Type'  => $cv->getMimeType(),
                ],
                'body' => fopen($cv->getPathname(), 'r'),
                'verify' => false,
                'timeout' => 30,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'upload_failed', 'msg' => $e->getMessage()], 500);
        }

        $job = Job::find($request->job_id);
        $employer_id = $job->user_id; // contoh: employer = owner job

        // Simpan nama file / path ke DB
        $application = new JobApplication();
        $application->job_id       = $request->job_id;
        $application->user_id      = Auth::user()->id;
        $application->employer_id  = $employer_id;
        $application->cv_path      = $fileName;
        $application->applied_date = now();
        $application->save();

        return response()->json(['status' => true, 'errors' => []]);



        // $job_id = $request->job_id;
        // // --- 1. Pastikan user login ---
        // if (!auth()->check()) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        // // --- 2. Validasi request ---
        // $request->validate([
        //     'job_id' => 'required|integer|exists:jobs,id',
        //     'cv'     => 'required|file|mimes:pdf,doc,docx|max:2048'
        // ]);

        // // --- 3. Upload file ---
        // $file = $request->file('cv');
        // $filename = time() . '_' . $file->getClientOriginalName();
        // $path = $file->storeAs('cv', $filename, 'public');

        // // --- 4. Ambil employer dari job ---
        // $job = Job::find($request->job_id);
        // $employer_id = $job->user_id; // contoh: employer = owner job

        // // --- 5. Simpan ke database (manual) ---
        // $application = new JobApplication();
        // $application->job_id       = $request->job_id;
        // $application->user_id      = Auth::user()->id;
        // $application->employer_id  = $employer_id;
        // $application->cv_path      = $path;
        // $application->applied_date = now();
        // $application->save();

        // // --- 6. Response sukses ---
        // return response()->json(['message' => 'Berhasil apply!']);
    }

    public function downloadCV($id)
    {
        // Cari record JobApplication
        $application = JobApplication::findOrFail($id);

        // Opsional: batasi user hanya bisa download miliknya atau jika HRD owner job
        if (auth()->id() != $application->user_id && auth()->id() != $application->job->user_id) {
            abort(403, 'Unauthorized access');
        }

        $supabaseUrl = rtrim(env('SUPABASE_URL'), '/');
        $bucket = env('SUPABASE_BUCKET', 'store_cv');
        $serviceRoleKey = env('SERVICE_ROLE_KEY'); // atau gunakan anon key kalau bucket public
        $fileName = $application->cv_path;

        $fileUrl = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$fileName}";

        $client = new Client();


        // GET FILE CV SUPABASE
        try {
            $resp = $client->get($fileUrl, [
                'headers' => [
                    'Authorization' => "Bearer {$serviceRoleKey}",
                ],
                'stream' => true, // agar bisa langsung stream file
                'verify' => false,
            ]);

            $contentType = $resp->getHeaderLine('Content-Type') ?: 'application/pdf';
            $disposition = 'attachment; filename="' . $fileName . '"';

            return Response::stream(function () use ($resp) {
                echo $resp->getBody()->getContents();
            }, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => $disposition
            ]);
        } catch (\Exception $e) {
            abort(404, 'CV file not found on Supabase.');
        }



        // // Cari CV berdasarkan application id dan user login
        // $application = JobApplication::where('id', $id)
        //     ->where('user_id', auth()->id()) // optional: batasi user hanya bisa download miliknya
        //     ->firstOrFail();

        // $path = storage_path('app/public/' . $application->cv_path);

        // if (!file_exists($path)) {
        //     abort(404, 'CV file not found.');
        // }

        // return response()->download($path);
    }
}
