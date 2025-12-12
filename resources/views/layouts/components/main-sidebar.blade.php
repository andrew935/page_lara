
			<aside class="app-sidebar sticky" id="sidebar">

				<!-- Start::main-sidebar-header -->
				<div class="main-sidebar-header">
					<a href="{{url('index')}}" class="header-logo">
						<img src="{{asset('build/assets/images/brand-logos/desktop-logo.png')}}" alt="logo" class="desktop-logo">
						<img src="{{ asset('img/2.jpg') }}" alt="logo" class="toggle-dark">
						<img src="{{asset('build/assets/images/brand-logos/desktop-dark.png')}}" alt="logo" class="desktop-dark">
						<img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="toggle-logo">
					</a>
				</div>
				<!-- End::main-sidebar-header -->

				<!-- Start::main-sidebar -->
				<div class="main-sidebar" id="sidebar-scroll">

					<!-- Start::nav -->
					<nav class="main-menu-container nav nav-pills flex-column sub-open">
						<div class="slide-left" id="slide-left">
							<svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path> </svg>
						</div>
						<ul class="main-menu">
							<!-- Start::slide__category -->
							<li class="slide__category"><span class="category-name">Main</span></li>
							<!-- End::slide__category -->

							<!-- Start::slide -->
							<li class="slide has-sub">
								<a href="javascript:void(0);" class="side-menu__item">
									<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M133.66,34.34a8,8,0,0,0-11.32,0L40,116.69V216h64V152h48v64h64V116.69Z" opacity="0.2"/><line x1="16" y1="216" x2="240" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="152 216 152 152 104 152 104 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="116.69" x2="40" y2="216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="216" x2="216" y2="116.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M24,132.69l98.34-98.35a8,8,0,0,1,11.32,0L232,132.69" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
									<span class="side-menu__label">Dashboards</span>
									<i class="ri-arrow-right-s-line side-menu__angle"></i>
								</a>
								<ul class="slide-menu child1">
									<li class="slide side-menu__label1">
										<a href="javascript:void(0)">Dashboards</a>
									</li>
									<li class="slide {{ request()->is('index') ? 'active' : '' }}">
										<a href="{{url('index')}}" class="side-menu__item"> 
											<svg xmlns="http://www.w3.org/2000/svg" class="side-menu-doublemenu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M54.46,201.54c-9.2-9.2-3.1-28.53-7.78-39.85C41.82,150,24,140.5,24,128s17.82-22,22.68-33.69C51.36,83,45.26,63.66,54.46,54.46S83,51.36,94.31,46.68C106.05,41.82,115.5,24,128,24S150,41.82,161.69,46.68c11.32,4.68,30.65-1.42,39.85,7.78s3.1,28.53,7.78,39.85C214.18,106.05,232,115.5,232,128S214.18,150,209.32,161.69c-4.68,11.32,1.42,30.65-7.78,39.85s-28.53,3.1-39.85,7.78C150,214.18,140.5,232,128,232s-22-17.82-33.69-22.68C83,204.64,63.66,210.74,54.46,201.54Z" opacity="0.2"/><path d="M54.46,201.54c-9.2-9.2-3.1-28.53-7.78-39.85C41.82,150,24,140.5,24,128s17.82-22,22.68-33.69C51.36,83,45.26,63.66,54.46,54.46S83,51.36,94.31,46.68C106.05,41.82,115.5,24,128,24S150,41.82,161.69,46.68c11.32,4.68,30.65-1.42,39.85,7.78s3.1,28.53,7.78,39.85C214.18,106.05,232,115.5,232,128S214.18,150,209.32,161.69c-4.68,11.32,1.42,30.65-7.78,39.85s-28.53,3.1-39.85,7.78C150,214.18,140.5,232,128,232s-22-17.82-33.69-22.68C83,204.64,63.66,210.74,54.46,201.54Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="96" cy="96" r="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="160" cy="160" r="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="88" y1="168" x2="168" y2="88" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
											Sales</a>
									</li>
								</ul>
								<!-- Start::slide -->
								<li class="slide has-sub {{ request()->is('domains*') ? 'active open' : '' }}">
									<a href="javascript:void(0);" class="side-menu__item">
										<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="96" opacity="0.2"/><circle cx="128" cy="128" r="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="37.5" y1="96" x2="218.5" y2="96" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="37.5" y1="160" x2="218.5" y2="160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
										<span class="side-menu__label">Domains</span>
										<i class="ri-arrow-right-s-line side-menu__angle"></i>
									</a>
									<ul class="slide-menu child1">
										<li class="slide {{ request()->is('domains') ? 'active' : '' }}">
											<a href="{{ route('domains.index') }}" class="side-menu__item">Domains</a>
										</li>
										<li class="slide {{ request()->is('domains/settings') ? 'active' : '' }}">
											<a href="{{ route('domains.settings.edit') }}" class="side-menu__item">Settings</a>
										</li>
									</ul>
								</li>
								<!-- End::slide -->
							</li>
							<!-- End::slide -->

							<!-- Start::slide__category -->
						<li class="slide__category"><span class="category-name">Connections</span></li>
						<!-- End::slide__category -->

						<!-- Start::slide -->
						<li class="slide {{ request()->is('connections/telegram') ? 'active' : '' }}">
							<a href="{{ route('connections.telegram.edit') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M224,56a24,24,0,0,0-24-24L56,32A24,24,0,0,0,32,56V200a24,24,0,0,0,24,24H200a24,24,0,0,0,24-24Z" opacity="0.2"/><rect x="32" y="32" width="192" height="192" rx="24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M80,112a24,24,0,1,1,24,24H80v24h40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="152" y1="112" x2="176" y2="112" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="152" y1="144" x2="176" y2="144" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<span class="side-menu__label">Telegram</span>
							</a>
						</li>
						<!-- End::slide -->

						<!-- Start::slide__category -->
						<li class="slide__category"><span class="category-name">Administration</span></li>
						<!-- End::slide__category -->

						

						<!-- Start::slide -->
						<li class="slide {{ request()->is('users*') ? 'active' : '' }}">
							<a href="{{ route('users.index') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="96" r="64" opacity="0.2"/><circle cx="128" cy="96" r="64" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M30.989,215.99064a112.03731,112.03731,0,0,1,194.02311.002" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<span class="side-menu__label">Users</span>
							</a>
						</li>
						<!-- End::slide -->

						<!-- Start::slide -->
						<li class="slide {{ request()->is('roles*') ? 'active' : '' }}">
							<a href="{{ route('roles.index') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="88" cy="108" r="52" opacity="0.2"/><circle cx="88" cy="108" r="52" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M155.41251,57.937A52.00595,52.00595,0,1,1,169.52209,160" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M15.99613,197.39669a88.01736,88.01736,0,0,1,143.97535.00389" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M169.52209,160a87.89491,87.89491,0,0,1,72.00032,37.3912" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<span class="side-menu__label">Roles</span>
							</a>
						</li>
						<!-- End::slide -->

						<!-- Start::slide -->
						<li class="slide {{ request()->is('permissions*') ? 'active' : '' }}">
							<a href="{{ route('permissions.index') }}" class="side-menu__item">
								<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M128,216S24,160,24,80A24,24,0,0,1,48,56c16,0,24,8,40,8s32-16,40-16,24,8,40,8,24-8,40-8a24,24,0,0,1,24,24c0,80-104,136-104,136Z" opacity="0.2"/><path d="M128,216S24,160,24,80A24,24,0,0,1,48,56c16,0,24,8,40,8s32-16,40-16,24,8,40,8,24-8,40-8a24,24,0,0,1,24,24c0,80-104,136-104,136Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
								<span class="side-menu__label">Permissions</span>
							</a>
						</li>
						<!-- End::slide -->

                        


						</ul>
						<ul class="doublemenu_bottom-menu main-menu mb-0 border-top">
							<!-- Start::slide -->
							<li class="slide">
								<a href="javascript:void(0);" class="side-menu__item layout-setting-doublemenu">
									<span class="light-layout">
										<!-- Start::header-link-icon -->
										<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" opacity="0.2"/><path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
										<!-- End::header-link-icon -->
									</span>
									<span class="dark-layout">
										<!-- Start::header-link-icon -->
										<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><circle cx="128" cy="128" r="56" opacity="0.2"/><line x1="128" y1="40" x2="128" y2="32" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="128" r="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="64" x2="56" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="64" y1="192" x2="56" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="64" x2="200" y2="56" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="192" y1="192" x2="200" y2="200" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="40" y1="128" x2="32" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="128" y1="216" x2="128" y2="224" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="216" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
										<!-- End::header-link-icon -->
									</span>
									<span class="side-menu__label">Theme Settings</span>
								</a>
							</li>
							<!-- End::slide -->
							<!-- Start::slide -->
							<li class="slide">
								<a href="{{url('sign-in-cover')}}" class="side-menu__item">
									<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M48,40H208a16,16,0,0,1,16,16V200a16,16,0,0,1-16,16H48a0,0,0,0,1,0,0V40A0,0,0,0,1,48,40Z" opacity="0.2"/><polyline points="112 40 48 40 48 216 112 216" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="112" y1="128" x2="224" y2="128" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><polyline points="184 88 224 128 184 168" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
									<span class="side-menu__label">Logout</span>
								</a>
							</li>
							<!-- End::slide -->
							<!-- Start::slide -->
							<li class="slide">
								<a href="{{url('profile-settings')}}" class="side-menu__item">
									<svg xmlns="http://www.w3.org/2000/svg" class="side-menu__icon" viewBox="0 0 256 256"><rect width="256" height="256" fill="none"/><path d="M205.31,71.08a16,16,0,0,1-20.39-20.39A96,96,0,0,0,63.8,199.38h0A72,72,0,0,1,128,160a40,40,0,1,1,40-40,40,40,0,0,1-40,40,72,72,0,0,1,64.2,39.37A96,96,0,0,0,205.31,71.08Z" opacity="0.2"/><line x1="200" y1="40" x2="200" y2="28" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="200" cy="56" r="16" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="186.14" y1="48" x2="175.75" y2="42" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="186.14" y1="64" x2="175.75" y2="70" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="200" y1="72" x2="200" y2="84" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="213.86" y1="64" x2="224.25" y2="70" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><line x1="213.86" y1="48" x2="224.25" y2="42" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><circle cx="128" cy="120" r="40" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M63.8,199.37a72,72,0,0,1,128.4,0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/><path d="M222.67,112A95.92,95.92,0,1,1,144,33.33" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16"/></svg>
									<span class="side-menu__label">Profile Settings</span>
								</a>
							</li>
							<!-- End::slide -->
							<!-- Start::slide -->
							<li class="slide">
								<a href="{{url('profile')}}" class="side-menu__item p-1 rounded-circle mb-0">
									<span class="avatar avatar-md avatar-rounded">
										<img src="{{asset('build/assets/images/faces/10.jpg')}}" alt="">
									</span>
								</a>
							</li>
							<!-- End::slide -->
						</ul>
						<div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"> <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"></path> </svg></div>
					</nav>
					<!-- End::nav -->

				</div>
				<!-- End::main-sidebar -->

			</aside>