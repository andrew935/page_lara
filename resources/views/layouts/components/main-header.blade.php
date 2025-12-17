
			<header class="app-header sticky" id="header">

				<!-- Start::main-header-container -->
				<div class="main-header-container container-fluid">

					<!-- Start::header-content-left -->
					<div class="header-content-left">

						<!-- Start::header-element -->
						<div class="header-element">
							<div class="horizontal-logo">
								<a href="{{url('index')}}" class="header-logo">
									<img src="{{asset('img/logo.png')}}" alt="logo" class="toggle-logo">
								    <img src="{{asset('img/logo.png')}}" alt="logo" class="desktop-dark"> 
									<img src="{{asset('img/logo.png')}}" alt="logo" class="toggle-logo">
									<img src="{{asset('img/logo.png')}}" alt="logo" class="toggle-dark">
									{{-- <img src="{{asset('build/assets/images/brand-logos/desktop-logo.png')}}" alt="logo" class="desktop-logo"> --}}
									{{-- <img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="toggle-logo"> --}}
									{{-- <img src="{{asset('build/assets/images/brand-logos/desktop-dark.png')}}" alt="logo" class="desktop-dark"> --}}
									{{-- <img src="{{asset('build/assets/images/brand-logos/toggle-dark.png')}}" alt="logo" class="toggle-dark"> --}}
								</a>
							</div>
						</div>
						<!-- End::header-element -->

						<!-- Start::header-element -->
						<div class="header-element mx-lg-0 mx-2">
							<a aria-label="Hide Sidebar" class="sidemenu-toggle header-link animated-arrow hor-toggle horizontal-navtoggle" data-bs-toggle="sidebar" href="javascript:void(0);"><span></span></a>
						</div>
						<!-- End::header-element -->

						

					</div>
					<!-- End::header-content-left -->

					<!-- Start::header-content-right -->
					<ul class="header-content-right">

						 

					

						
 

						

						 
						 
						<!-- Start::header-element -->
						<li class="header-element dropdown">
							<!-- Start::header-link|dropdown-toggle -->
							<a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
								@php
									$userName = optional(auth()->user())->name;
									$initials = $userName
										? mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $userName), 0, 2))
										: 'US';
								@endphp
								<span class="d-inline-flex align-items-center justify-content-center avatar avatar-sm avatar-rounded bg-primary text-white fw-bold">
									{{ $initials }}
								</span>
							</a>
							<!-- End::header-link|dropdown-toggle -->
							<div class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end" aria-labelledby="mainHeaderProfile">
								<div class="p-3 bg-primary text-fixed-white">
									<div class="d-flex align-items-center justify-content-between">
										<p class="mb-0 fs-16">Profile</p>
										{{-- settings removed --}}
									</div>
								</div>
								<div class="dropdown-divider"></div>
								<div class="p-3">
									<div class="d-flex align-items-start gap-2">
										<div class="lh-1">
											<span class="avatar avatar-sm bg-primary-transparent avatar-rounded">
												<img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="">
											</span>
										</div>
										<div>
											<span class="d-block fw-semibold lh-1">{{ auth()->user()->name ?? 'Account' }}</span>
											<span class="text-muted fs-12">{{ auth()->user()->email ?? '' }}</span>
										</div>
									</div>
								</div>
								<div class="dropdown-divider"></div>
								<ul class="list-unstyled mb-0">
									<li>
										<ul class="list-unstyled mb-0 sub-list">
											<li>
												<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);"><i class="ti ti-user-circle me-2 fs-18"></i>View Profile</a>
											</li>
										</ul>        
									</li>
									<li>
										<ul class="list-unstyled mb-0 sub-list">
											<li>
												<a class="dropdown-item d-flex align-items-center" href="javascript:void(0);"><i class="ti ti-lifebuoy me-2 fs-18"></i>Support</a>
											</li>
										</ul>        
									</li>
									<li>
										<form method="POST" action="{{ route('logout') }}">
											@csrf
											<button type="submit" class="dropdown-item d-flex align-items-center">
												<i class="ti ti-logout me-2 fs-18"></i>Logout
											</button>
										</form>
									</li>
								</ul>
							</div>
						</li>  
						<!-- End::header-element -->


					</ul>
					<!-- End::header-content-right -->

				</div>
				<!-- End::main-header-container -->

			</header>
					