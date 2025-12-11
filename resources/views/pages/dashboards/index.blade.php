
@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
                        <div>
                            <h1 class="page-title fw-medium fs-20 mb-0">Dashboard</h1>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-group">
                                <input type="text" class="form-control breadcrumb-input" id="daterange" placeholder="Search By Date Range">
                            </div>
                            <div class="btn-list">
                                <button class="btn btn-icon btn-primary btn-wave">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button class="btn btn-icon btn-primary btn-wave me-0">
                                    <i class="ri-filter-3-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::app-content -->
                    <div class="row row-sm mt-lg-4">
                        <div class="col-sm-12 col-lg-12 col-xl-12">
                            <div class="card bg-primary custom-card card-box">
                                <div class="card-body p-4">
                                    <span class="text-fixed-white">NOTE:</span>      
                                    <p class="text-fixed-white mt-2 mb-0">Thank you for choosing our template.. if you want to create your own customised project take a refrence of our project and you can implement here.</p>              
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End::app-content -->
                     
@endsection

@section('scripts')
        


@endsection
