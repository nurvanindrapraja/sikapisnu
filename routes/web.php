<?php

use App\Http\Controllers\Admin\AuditLogController as AdminAuditLog;
use App\Http\Controllers\Admin\CardOrderController as AdminCardOrder;
use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\LocationController as AdminLocation;
use App\Http\Controllers\Admin\MemberController as AdminMember;
use App\Http\Controllers\Admin\MwcController as AdminMwc;
use App\Http\Controllers\Admin\OfficerController as AdminOfficer;
use App\Http\Controllers\Admin\ProfileController as AdminProfile;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\VerificationController as AdminVerification;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Member\CardOrderController as MemberCardOrder;
use App\Http\Controllers\Member\DashboardController as MemberDashboard;
use App\Http\Controllers\Member\ProfileController as MemberProfile;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

// Location API Route (Public for register & member profile)
Route::get('/api/locations', [LocationController::class, 'getLocations'])->name('api.locations');

// 1. Public Routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tentang', [HomeController::class, 'tentang'])->name('tentang');
Route::get('/daftar-anggota', [HomeController::class, 'daftarAnggota'])->name('daftar.anggota');

// QR Verification Routes
Route::get('/verify/{qr_token}', [VerificationController::class, 'verify'])->name('verify.card');
Route::get('/card/{qr_token}', [VerificationController::class, 'verify']);
Route::get('/qr-code/{qr_token}.png', [VerificationController::class, 'qrImage'])->name('verify.qr');

// 2. Auth Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/check-email', [AuthController::class, 'checkEmail'])->name('check.email');
Route::get('/activate/{token}', [AuthController::class, 'activateAccount'])->name('activate.account');

// 3. Member Area Routes
Route::middleware(['auth'])->prefix('member')->name('member.')->group(function () {
    Route::get('/dashboard', [MemberDashboard::class, 'index'])->name('dashboard');
    Route::get('/card/download', [MemberDashboard::class, 'downloadCard'])->name('card.download');
    Route::get('/cv/download', [MemberDashboard::class, 'downloadCv'])->name('cv.download');

    // Physical Card Request
    Route::post('/card-order', [MemberCardOrder::class, 'store'])->name('card_order.store');
    Route::post('/card-order/{id}/receive', [MemberCardOrder::class, 'receive'])->name('card_order.receive');

    // Profile Management
    Route::get('/profile/edit', [MemberProfile::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [MemberProfile::class, 'update'])->name('profile.update');
    Route::post('/profile/change-password', [MemberProfile::class, 'changePassword'])->name('profile.change_password');

    Route::post('/profile/education', [MemberProfile::class, 'addEducation'])->name('profile.education.add');
    Route::put('/profile/education/{id}', [MemberProfile::class, 'updateEducation'])->name('profile.education.update');
    Route::delete('/profile/education/{id}', [MemberProfile::class, 'deleteEducation'])->name('profile.education.delete');

    Route::post('/profile/organization', [MemberProfile::class, 'addOrganization'])->name('profile.organization.add');
    Route::put('/profile/organization/{id}', [MemberProfile::class, 'updateOrganization'])->name('profile.organization.update');
    Route::delete('/profile/organization/{id}', [MemberProfile::class, 'deleteOrganization'])->name('profile.organization.delete');

    Route::post('/profile/employment', [MemberProfile::class, 'addEmployment'])->name('profile.employment.add');
    Route::put('/profile/employment/{id}', [MemberProfile::class, 'updateEmployment'])->name('profile.employment.update');
    Route::delete('/profile/employment/{id}', [MemberProfile::class, 'deleteEmployment'])->name('profile.employment.delete');

    Route::post('/profile/nu-training', [MemberProfile::class, 'addNuTraining'])->name('profile.nu_training.add');
    Route::put('/profile/nu-training/{id}', [MemberProfile::class, 'updateNuTraining'])->name('profile.nu_training.update');
    Route::delete('/profile/nu-training/{id}', [MemberProfile::class, 'deleteNuTraining'])->name('profile.nu_training.delete');

    Route::post('/profile/certification', [MemberProfile::class, 'addCertification'])->name('profile.certification.add');
    Route::put('/profile/certification/{id}', [MemberProfile::class, 'updateCertification'])->name('profile.certification.update');
    Route::delete('/profile/certification/{id}', [MemberProfile::class, 'deleteCertification'])->name('profile.certification.delete');
});

// 4. Admin Area Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboard::class, 'index'])->name('dashboard');

    // Admin Profile & Change Password
    Route::get('/profile', [AdminProfile::class, 'index'])->name('profile.index');
    Route::put('/profile', [AdminProfile::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/change-password', [AdminProfile::class, 'changePassword'])->name('profile.change_password');

    // Verifikasi Anggota
    Route::get('/verifikasi', [AdminVerification::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/{id}', [AdminVerification::class, 'show'])->name('verifikasi.show');
    Route::post('/verifikasi/{id}/verify-account', [AdminVerification::class, 'verifyAccount'])->name('verifikasi.verify_account');
    Route::post('/verifikasi/{id}/approve', [AdminVerification::class, 'approve'])->name('verifikasi.approve');
    Route::post('/verifikasi/{id}/revision', [AdminVerification::class, 'requestRevision'])->name('verifikasi.revision');
    Route::post('/verifikasi/{id}/reject', [AdminVerification::class, 'reject'])->name('verifikasi.reject');

    // Database Potensi & Anggota
    Route::get('/anggota', [AdminMember::class, 'index'])->name('anggota.index');
    Route::get('/anggota/{id}', [AdminMember::class, 'show'])->name('anggota.show');
    Route::get('/anggota/{id}/edit', [AdminMember::class, 'edit'])->name('anggota.edit');
    Route::get('/anggota/{id}/cv', [AdminMember::class, 'downloadCv'])->name('anggota.cv.download');
    Route::put('/anggota/{id}', [AdminMember::class, 'update'])->name('anggota.update');
    Route::delete('/anggota/{id}', [AdminMember::class, 'destroy'])->name('anggota.destroy');

    // Admin Member Background History Management
    Route::post('/anggota/{id}/education', [AdminMember::class, 'addEducation'])->name('anggota.education.add');
    Route::put('/anggota/{id}/education/{eduId}', [AdminMember::class, 'updateEducation'])->name('anggota.education.update');
    Route::delete('/anggota/{id}/education/{eduId}', [AdminMember::class, 'deleteEducation'])->name('anggota.education.delete');

    Route::post('/anggota/{id}/organization', [AdminMember::class, 'addOrganization'])->name('anggota.organization.add');
    Route::put('/anggota/{id}/organization/{orgId}', [AdminMember::class, 'updateOrganization'])->name('anggota.organization.update');
    Route::delete('/anggota/{id}/organization/{orgId}', [AdminMember::class, 'deleteOrganization'])->name('anggota.organization.delete');

    Route::post('/anggota/{id}/employment', [AdminMember::class, 'addEmployment'])->name('anggota.employment.add');
    Route::put('/anggota/{id}/employment/{empId}', [AdminMember::class, 'updateEmployment'])->name('anggota.employment.update');
    Route::delete('/anggota/{id}/employment/{empId}', [AdminMember::class, 'deleteEmployment'])->name('anggota.employment.delete');

    Route::post('/anggota/{id}/nu-training', [AdminMember::class, 'addNuTraining'])->name('anggota.nu_training.add');
    Route::put('/anggota/{id}/nu-training/{nuId}', [AdminMember::class, 'updateNuTraining'])->name('anggota.nu_training.update');
    Route::delete('/anggota/{id}/nu-training/{nuId}', [AdminMember::class, 'deleteNuTraining'])->name('anggota.nu_training.delete');

    Route::post('/anggota/{id}/certification', [AdminMember::class, 'addCertification'])->name('anggota.certification.add');
    Route::put('/anggota/{id}/certification/{certId}', [AdminMember::class, 'updateCertification'])->name('anggota.certification.update');
    Route::delete('/anggota/{id}/certification/{certId}', [AdminMember::class, 'deleteCertification'])->name('anggota.certification.delete');

    // Data Sampah & Pemulihan
    Route::get('/sampah', [AdminMember::class, 'trash'])->name('sampah.index');
    Route::post('/sampah/{id}/restore', [AdminMember::class, 'restore'])->name('sampah.restore');
    Route::delete('/sampah/{id}/force-delete', [AdminMember::class, 'forceDelete'])->name('sampah.force_delete');

    // Pengurus Management
    Route::get('/pengurus', [AdminOfficer::class, 'index'])->name('pengurus.index');
    Route::post('/pengurus/{member_id}/promote', [AdminOfficer::class, 'promote'])->name('pengurus.promote');
    Route::post('/pengurus/{member_id}/demote', [AdminOfficer::class, 'demote'])->name('pengurus.demote');

    // MWC & PAC ISNU
    Route::get('/mwc', [AdminMwc::class, 'index'])->name('mwc.index');
    Route::post('/mwc', [AdminMwc::class, 'storeMwc'])->name('mwc.store');
    Route::post('/pac', [AdminMwc::class, 'storePac'])->name('pac.store');
    Route::put('/pac/{id}', [AdminMwc::class, 'updatePac'])->name('pac.update');
    Route::delete('/pac/{id}', [AdminMwc::class, 'destroyPac'])->name('pac.destroy');

    // Pemesanan Kartu Anggota Fisik
    Route::get('/card-orders', [AdminCardOrder::class, 'index'])->name('card_orders.index');
    Route::patch('/card-orders/{id}/status', [AdminCardOrder::class, 'updateStatus'])->name('card_orders.update_status');
    Route::delete('/card-orders/{id}', [AdminCardOrder::class, 'destroy'])->name('card_orders.destroy');

    // Master Lokasi Berjenjang
    Route::get('/locations', [AdminLocation::class, 'index'])->name('locations.index');
    Route::post('/locations', [AdminLocation::class, 'store'])->name('locations.store');
    Route::put('/locations/{id}', [AdminLocation::class, 'update'])->name('locations.update');
    Route::delete('/locations/{id}', [AdminLocation::class, 'destroy'])->name('locations.destroy');

    // Laporan & Export
    Route::get('/laporan', [AdminReport::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [AdminReport::class, 'export'])->name('laporan.export');

    // Audit Log
    Route::get('/audit-log', [AdminAuditLog::class, 'index'])->name('audit.index');
});
