<?php

namespace App\Http\Controllers;

use App\Services\Onboarding\OnboardingManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /**
     * 온보딩 관리자
     */
    protected $onboardingManager;
    
    /**
     * 생성자
     *
     * @param OnboardingManager $onboardingManager
     */
    public function __construct(OnboardingManager $onboardingManager)
    {
        $this->onboardingManager = $onboardingManager;
        $this->middleware('auth');
    }
    
    /**
     * 사용자의 온보딩 상태를 가져옵니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(Request $request)
    {
        $user = Auth::user();
        $status = $this->onboardingManager->getUserOnboardingStatus($user);
        
        return response()->json($status);
    }
    
    /**
     * 사용자의 온보딩 진행 상황을 가져옵니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProgress(Request $request)
    {
        $user = Auth::user();
        $progress = $this->onboardingManager->getUserOnboardingProgress($user);
        
        return response()->json($progress);
    }
    
    /**
     * 특정 투어 정의를 가져옵니다.
     *
     * @param Request $request
     * @param string $tourId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTour(Request $request, string $tourId)
    {
        $tour = $this->onboardingManager->getTour($tourId);
        
        if (!$tour) {
            return response()->json(['error' => '투어를 찾을 수 없습니다.'], 404);
        }
        
        $user = Auth::user();
        $completed = $this->onboardingManager->hasTourCompleted($user, $tourId);
        
        return response()->json([
            'id' => $tourId,
            'title' => $tour['title'],
            'description' => $tour['description'],
            'steps' => $tour['steps'],
            'completed' => $completed
        ]);
    }
    
    /**
     * 모든 투어 정의를 가져옵니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTours(Request $request)
    {
        $tours = $this->onboardingManager->getAllTours();
        $user = Auth::user();
        $result = [];
        
        foreach ($tours as $tourId => $tour) {
            $completed = $this->onboardingManager->hasTourCompleted($user, $tourId);
            
            $result[] = [
                'id' => $tourId,
                'title' => $tour['title'],
                'description' => $tour['description'],
                'completed' => $completed
            ];
        }
        
        return response()->json($result);
    }
    
    /**
     * 투어를 완료로 표시합니다.
     *
     * @param Request $request
     * @param string $tourId
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeTour(Request $request, string $tourId)
    {
        $user = Auth::user();
        $success = $this->onboardingManager->markTourAsCompleted($user, $tourId);
        
        if (!$success) {
            return response()->json(['error' => '투어 완료 상태를 업데이트하는 중 오류가 발생했습니다.'], 500);
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * 현재 투어를 설정합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function setCurrentTour(Request $request)
    {
        $request->validate([
            'tour_id' => 'nullable|string',
            'progress' => 'nullable|integer|min:0'
        ]);
        
        $user = Auth::user();
        $tourId = $request->input('tour_id');
        $progress = $request->input('progress', 0);
        
        $success = $this->onboardingManager->setCurrentTour($user, $tourId, $progress);
        
        if (!$success) {
            return response()->json(['error' => '현재 투어를 설정하는 중 오류가 발생했습니다.'], 500);
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * 투어 진행 상황을 업데이트합니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProgress(Request $request)
    {
        $request->validate([
            'progress' => 'required|integer|min:0'
        ]);
        
        $user = Auth::user();
        $progress = $request->input('progress');
        
        $success = $this->onboardingManager->updateTourProgress($user, $progress);
        
        if (!$success) {
            return response()->json(['error' => '투어 진행 상황을 업데이트하는 중 오류가 발생했습니다.'], 500);
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * 사용자에게 추천할 다음 투어를 가져옵니다.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNextRecommendedTour(Request $request)
    {
        $user = Auth::user();
        $nextTour = $this->onboardingManager->getNextRecommendedTour($user);
        
        if (!$nextTour) {
            return response()->json(['message' => '모든 투어를 완료했습니다.']);
        }
        
        return response()->json($nextTour);
    }
    
    /**
     * 온보딩 페이지를 표시합니다.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        $progress = $this->onboardingManager->getUserOnboardingProgress($user);
        $tours = $this->onboardingManager->getAllTours();
        
        $tourList = [];
        
        foreach ($tours as $tourId => $tour) {
            $completed = $this->onboardingManager->hasTourCompleted($user, $tourId);
            
            $tourList[] = [
                'id' => $tourId,
                'title' => $tour['title'],
                'description' => $tour['description'],
                'completed' => $completed
            ];
        }
        
        return view('onboarding.index', [
            'progress' => $progress,
            'tours' => $tourList
        ]);
    }
    
    /**
     * 특정 투어 페이지를 표시합니다.
     *
     * @param string $tourId
     * @return \Illuminate\View\View
     */
    public function showTour(string $tourId)
    {
        $tour = $this->onboardingManager->getTour($tourId);
        
        if (!$tour) {
            abort(404, '투어를 찾을 수 없습니다.');
        }
        
        $user = Auth::user();
        $completed = $this->onboardingManager->hasTourCompleted($user, $tourId);
        
        return view('onboarding.tour', [
            'tourId' => $tourId,
            'tour' => $tour,
            'completed' => $completed
        ]);
    }
} 