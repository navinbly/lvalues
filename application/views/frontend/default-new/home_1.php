<!---------- Banner Section Start ---------------->
<?php
    $lvalues_banner_image = get_current_banner('banner_image');
    if(empty($lvalues_banner_image)){
        $lvalues_banner_image = 'home_1.png';
    }
?>
<section class="h-1-banner bannar-area lvalues-home-hero" id="lvaluesHomeTop">
    <style>
        .lvalues-home-hero{
            background:#f7f9ff;
            background:-webkit-linear-gradient(135deg,#f7f9ff 0%,#ffffff 48%,#f4f0ff 100%);
            background:linear-gradient(135deg,#f7f9ff 0%,#ffffff 48%,#f4f0ff 100%);
            padding:54px 0 46px;
            overflow:hidden;
        }
        .lvalues-hero-kicker{
            display:inline-block;
            padding:8px 14px;
            border-radius:999px;
            background:#ffffff;
            color:#6c4df6;
            border:1px solid #e5ddff;
            font-size:13px;
            font-weight:700;
            margin-bottom:16px;
            box-shadow:0 8px 22px rgba(40,35,95,.06);
        }
        .lvalues-hero-title{
            margin:0;
            color:#17213a;
            font-size:52px;
            line-height:1.12;
            font-weight:800;
            letter-spacing:-1.2px;
        }
        .lvalues-hero-title span{color:#6c4df6;}
        .lvalues-hero-subtitle{
            margin-top:18px;
            max-width:620px;
            color:#566179;
            font-size:18px;
            line-height:1.75;
        }
        .lvalues-confidence-box{
            margin-top:16px;
            max-width:620px;
            background:#ffffff;
            border:1px solid #eef0f7;
            border-left:4px solid #6c4df6;
            border-radius:16px;
            padding:15px 18px;
            box-shadow:0 12px 28px rgba(31,39,83,.07);
        }
        .lvalues-confidence-box h6{margin:0 0 6px;color:#17213a;font-weight:800;font-size:16px;}
        .lvalues-confidence-box p{margin:0;color:#6b7280;font-size:14px;line-height:1.6;}
        .lvalues-hero-actions{margin-top:26px;display:flex;flex-wrap:nowrap;gap:10px;align-items:center;}
        .lvalues-hero-actions .btn{
            border-radius:14px !important;
            padding:13px 22px;
            font-weight:800;
            margin-right:0;
            margin-bottom:0;
            box-shadow:none !important;
            white-space:nowrap;
        }
        .lvalues-btn-primary{
            background:#6c4df6 !important;
            border-color:#6c4df6 !important;
            color:#ffffff !important;
        }
        .lvalues-btn-primary:hover,
        .lvalues-btn-primary:focus{
            background:#5639de !important;
            border-color:#5639de !important;
            color:#ffffff !important;
        }
        .lvalues-btn-outline{
            background:#ffffff !important;
            border:1px solid #6c4df6 !important;
            color:#6c4df6 !important;
        }
        .lvalues-btn-outline:hover,
        .lvalues-btn-outline:focus{
            background:#f2efff !important;
            color:#5639de !important;
        }
        .lvalues-trust-row{margin-top:16px;color:#566179;font-size:14px;line-height:1.7;}
        .lvalues-trust-pill{
            display:inline-block;
            margin:0 8px 8px 0;
            padding:8px 12px;
            border-radius:999px;
            background:#ffffff;
            border:1px solid #edf0f7;
            color:#36415a;
            font-weight:700;
            box-shadow:0 8px 20px rgba(27,36,76,.05);
        }
        .lvalues-hero-image-card{
            position:relative;
            padding:12px;
            background:#ffffff;
            border:1px solid #eef0f7;
            border-radius:24px;
            box-shadow:0 24px 60px rgba(38,45,97,.12);
            max-width:640px;
            margin-left:auto;
        }
        .lvalues-hero-image-card img{
            width:100%;
            max-width:100%;
            height:auto;
            display:block;
            border-radius:18px;
        }
        .lvalues-floating-card{
            position:absolute;
            left:-18px;
            bottom:22px;
            width:210px;
            background:#ffffff;
            border-radius:18px;
            padding:14px;
            box-shadow:0 18px 45px rgba(31,39,83,.16);
            border:1px solid #eff1f7;
            text-align:left;
        }
        .lvalues-floating-card h6{margin:0 0 5px;color:#17213a;font-weight:800;font-size:15px;}
        .lvalues-floating-card p{margin:0;color:#6b7280;font-size:12px;line-height:1.45;}
        .lvalues-search-panel{
            display:block;
            margin-top:42px;
            background:#ffffff;
            border:1px solid #e9ecf5;
            border-radius:24px;
            padding:26px;
            box-shadow:0 22px 60px rgba(34,41,87,.10);
        }
        .lvalues-search-panel h4{margin:0 0 6px;color:#17213a;font-size:24px;font-weight:800;}
        .lvalues-search-panel p{margin-bottom:18px;color:#68738c;}
        .lvalues-search-back{
            display:inline-block;
            margin-bottom:12px;
            color:#6c4df6;
            font-weight:800;
            text-decoration:none;
            cursor:pointer;
        }
        .lvalues-search-panel.is-visible{display:block;}
        .lvalues-search-back:hover{text-decoration:underline;color:#5639de;}
        .lvalues-mode-buttons{
            display:inline-flex;
            flex-wrap:nowrap;
            align-items:center;
            gap:6px;
            margin-bottom:16px;
            background:#f2efff;
            border:1px solid #ded6ff;
            border-radius:999px;
            padding:6px;
            box-shadow:inset 0 1px 2px rgba(108,77,246,.08);
            max-width:100%;
        }
        .lvalues-mode-buttons button{
            width:auto !important;
            min-width:150px;
            height:46px;
            margin:0 !important;
            padding:0 22px;
            border:0 !important;
            border-radius:999px !important;
            background:transparent !important;
            color:#5f46e8 !important;
            font-weight:800;
            white-space:nowrap;
            box-shadow:none !important;
            transition:background .25s ease,color .25s ease,box-shadow .25s ease;
            -webkit-transition:background .25s ease,color .25s ease,box-shadow .25s ease;
        }
        .lvalues-mode-buttons button:hover,
        .lvalues-mode-buttons button:focus{
            background:#ffffff !important;
            color:#5f46e8 !important;
            outline:none;
        }
        .lvalues-mode-buttons button.active{
            background:#6c4df6 !important;
            background:-webkit-linear-gradient(135deg,#7c5cff,#5f46e8) !important;
            background:linear-gradient(135deg,#7c5cff,#5f46e8) !important;
            color:#ffffff !important;
            box-shadow:0 10px 22px rgba(108,77,246,.28) !important;
        }
        .lvalues-search-input{
            height:56px;
            border-radius:14px;
            border:1px solid #dce2ef;
            color:#1f2937;
            font-size:15px;
            box-shadow:none !important;
        }
        .lvalues-search-input:focus{border-color:#6c4df6;box-shadow:0 0 0 .15rem rgba(108,77,246,.15) !important;}
        .lvalues-search-submit{
            height:56px;
            border-radius:14px !important;
            font-weight:800;
            background:#6c4df6 !important;
            border-color:#6c4df6 !important;
        }
        .lvalues-hero-filter-grid{display:grid;grid-template-columns:1.2fr 1fr 1fr 1fr auto;gap:10px;align-items:end;}
        .lvalues-popular-tags{margin-top:16px;}
        .lvalues-popular-tags span,
        .lvalues-popular-tags button{display:inline-block;margin:0 8px 8px 0;}
        .lvalues-popular-tags span{color:#596579;font-weight:800;font-size:14px;}
        .lvalues-popular-tags button{
            border:0;
            border-radius:999px;
            background:#f4f1ff;
            color:#5d3df1;
            padding:8px 13px;
            font-size:13px;
            font-weight:800;
            cursor:pointer;
        }
        .lvalues-popular-tags button:hover{background:#6c4df6;color:#ffffff;}
        .lvalues-proof-grid{margin-top:26px;}
        .lvalues-proof-card{
            height:100%;
            background:rgba(255,255,255,.94);
            border:1px solid #edf0f7;
            border-radius:18px;
            padding:18px;
            box-shadow:0 14px 34px rgba(34,41,87,.07);
        }
        .lvalues-proof-card h5{margin:0 0 6px;color:#17213a;font-weight:800;font-size:17px;}
        .lvalues-proof-card p{margin:0;color:#6b7280;font-size:14px;line-height:1.55;}
        .lvalues-proof-icon{
            width:42px;height:42px;line-height:42px;text-align:center;border-radius:13px;
            background:#f4f1ff;color:#6c4df6;margin-bottom:12px;font-size:18px;
        }
        .lvalues-results-section{margin-top:30px;background:#17213a;border-radius:24px;padding:28px;color:#ffffff;box-shadow:0 20px 55px rgba(23,33,58,.18);}
        .lvalues-results-section h2{margin:0 0 8px;font-size:26px;font-weight:900;color:#ffffff;}
        .lvalues-results-section p{color:rgba(255,255,255,.78);line-height:1.7;margin:0;}
        .lvalues-result-card{height:100%;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.08);border-radius:16px;padding:18px;}
        .lvalues-result-card b{display:block;color:#ffffff;font-size:16px;margin-bottom:8px;}
        .lvalues-result-card span{display:block;color:rgba(255,255,255,.75);font-size:13px;line-height:1.65;}
        .lvalues-result-kicker{display:inline-flex;margin-bottom:12px;padding:7px 11px;border-radius:999px;background:#ffffff;color:#5f46e8;font-weight:900;font-size:12px;}
        .lvalues-section-heading{max-width:760px;margin:0 auto 30px;text-align:center;}
        .lvalues-section-heading h2{margin:0;color:#17213a;font-size:34px;line-height:1.25;font-weight:800;}
        .lvalues-section-heading p{margin:12px 0 0;color:#65708a;font-size:16px;line-height:1.7;}
        .lvalues-audience-section,.lvalues-work-section{background:#ffffff;padding:58px 0;}
        .lvalues-audience-card,.lvalues-work-card,.lvalues-outcome-card{
            height:100%;
            background:#ffffff;
            border:1px solid #e9ecf5;
            border-radius:18px;
            padding:22px;
            box-shadow:0 14px 34px rgba(34,41,87,.07);
        }
        .lvalues-audience-card h3,.lvalues-work-card h3,.lvalues-outcome-card h3{margin:0 0 10px;color:#17213a;font-size:19px;font-weight:800;}
        .lvalues-audience-card p,.lvalues-work-card p,.lvalues-outcome-card p{margin:0 0 16px;color:#65708a;font-size:14px;line-height:1.65;}
        .lvalues-audience-card a,.lvalues-work-card a{font-weight:800;color:#6c4df6;text-decoration:none;}
        .lvalues-audience-card a:hover,.lvalues-work-card a:hover{text-decoration:underline;}
        .lvalues-card-icon{
            width:46px;height:46px;line-height:46px;text-align:center;border-radius:14px;
            background:#edf8ff;color:#0b75bd;margin-bottom:15px;font-size:18px;
        }
        .lvalues-work-section{background:#f7f9ff;}
        .lvalues-outcome-strip{padding:42px 0;background:#17213a;color:#ffffff;}
        .lvalues-outcome-card{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.16);box-shadow:none;}
        .lvalues-outcome-card h3,.lvalues-outcome-card p{color:#ffffff;}
        .lvalues-outcome-card p{opacity:.82;}
        .lvalues-corporate-band{padding:42px 0;background:#ffffff;}
        .lvalues-corporate-panel{
            background:#f7f9ff;
            border:1px solid #e9ecf5;
            border-radius:22px;
            padding:28px;
        }
        .lvalues-corporate-panel h2{margin:0 0 10px;color:#17213a;font-size:30px;font-weight:800;}
        .lvalues-corporate-panel p{margin:0;color:#65708a;line-height:1.7;}
        .lvalues-corporate-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end;align-items:center;}
        @media (max-width:991px){
            .lvalues-home-hero{padding:36px 0 32px;}
            .lvalues-hero-title{font-size:40px;letter-spacing:-.8px;}
            .lvalues-hero-subtitle{font-size:16px;}
            .lvalues-hero-image-card{margin-top:24px;margin-left:0;}
            .lvalues-floating-card{display:none;}
            .lvalues-corporate-actions{justify-content:flex-start;margin-top:18px;}
        }
        @media (max-width:575px){
            .lvalues-home-hero{padding:28px 0;}
            .lvalues-hero-title{font-size:32px;line-height:1.18;}
            .lvalues-hero-subtitle{font-size:15px;line-height:1.65;}
            .lvalues-hero-actions{display:block;}
            .lvalues-hero-actions .btn{display:block;width:100%;margin-right:0;margin-bottom:10px;}
            .lvalues-search-panel{padding:18px;border-radius:18px;}
            .lvalues-mode-buttons{display:flex;width:100%;}
            .lvalues-mode-buttons button{min-width:0;flex:1 1 50%;padding:0 10px;font-size:14px;}
            .lvalues-hero-filter-grid{grid-template-columns:1fr 1fr;}
            .lvalues-search-submit{margin-top:10px;}
            .lvalues-section-heading h2{font-size:26px;}
            .lvalues-audience-section,.lvalues-work-section{padding:40px 0;}
        }
    </style>

    <div class="container">
        <div class="row align-items-start">
            <div class="col-lg-6 col-md-12">
                <div class="lvalues-hero-kicker">School • IT & Professional Courses • Online & Offline Learning</div>

					<h1 class="lvalues-hero-title">
						Learn school subjects.<br>
						Build <span>IT skills</span>.<br>
						Find tutors or courses.
					</h1>

					<p class="lvalues-hero-subtitle">
						Lvalues helps students learn school subjects, IT and professional courses through online classes, offline tuition, or hybrid learning. Students can find tutors and courses, while tutors can create batches, schedule sessions, assign tests, and track progress.
					</p>

					<div class="lvalues-confidence-box">
						<h6>One platform for complete learning</h6>
						<p>School courses, IT skills, professional training, tutors, batches, assignments, tests, and progress tracking — all in one place.</p>
					</div>

					<div class="lvalues-hero-actions">
						<a href="#lvaluesStartSearch" class="btn btn-primary btn-lg lvalues-btn-primary lvalues-open-search" data-mode="course">Find a Course</a>
						<a href="#lvaluesStartSearch" class="btn btn-outline-primary btn-lg lvalues-btn-outline lvalues-open-search" data-mode="tutor">Find a Tutor</a>
						<?php if($this->session->userdata('user_id')): ?>
							<a href="<?php echo site_url('user/become_an_instructor'); ?>" class="btn btn-outline-primary btn-lg lvalues-btn-outline">Become a Tutor</a>
						<?php else: ?>
							<a href="<?php echo site_url('sign_up?instructor=yes'); ?>" class="btn btn-outline-primary btn-lg lvalues-btn-outline">Become a Tutor</a>
						<?php endif; ?>
					</div>

					<div class="lvalues-trust-row">
						<span class="lvalues-trust-pill">School Courses</span>
						<span class="lvalues-trust-pill">IT & Professional Courses</span>
						<span class="lvalues-trust-pill">Online Classes</span>
						<span class="lvalues-trust-pill">Offline Tuition</span>
						<span class="lvalues-trust-pill">Hybrid Learning</span>
					</div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="lvalues-hero-image-card">
                    <img loading="eager" decoding="async" src="<?php echo base_url('uploads/system/'.$lvalues_banner_image.'?v=4'); ?>" onerror="this.onerror=null;this.src='<?php echo base_url('uploads/system/home_1.png?v=4'); ?>';" alt="Lvalues online and offline tutor student learning platform">

                </div>
            </div>
        </div>

        <div class="row" id="lvaluesStartSearch">
            <div class="col-lg-11 mx-auto">
                <div class="lvalues-search-panel is-visible">
                    <div class="row align-items-center">
                        <div class="col-lg-3 col-md-12">
                            <h4 id="lvaluesSearchTitle">Find a tutor</h4>
                            <p id="lvaluesSearchDescription">Search by subject, class, mode, and location. Online and offline learning are both supported.</p>
                        </div>
                        <div class="col-lg-9 col-md-12">
                            <form action="<?php echo site_url('home/search'); ?>" method="get" id="lvaluesHeroSearchForm">
                                <input type="hidden" name="search_for" id="lvaluesSearchFor" value="tutor">

                                <div class="lvalues-mode-buttons">
                                    <button type="button" class="btn active" data-mode="tutor">Find a tutor</button>
                                    <button type="button" class="btn" data-mode="course">Find a course</button>
                                </div>

                                <div class="lvalues-hero-filter-grid">
                                    <input type="text" name="query" id="lvaluesHeroQuery" class="form-control lvalues-search-input" placeholder="Subject, e.g. Math, English, Java">
                                    <input type="text" name="class_level" class="form-control lvalues-search-input" placeholder="Class / Level">
                                    <select name="mode" class="form-control lvalues-search-input">
                                        <option value="">Teaching Mode</option>
                                        <option value="online">Online</option>
                                        <option value="offline">Offline</option>
                                        <option value="hybrid">Hybrid</option>
                                    </select>
                                    <input type="text" name="location" class="form-control lvalues-search-input" placeholder="City / Pincode">
                                    <button class="btn btn-primary lvalues-search-submit" type="submit" data-lv-event="tutor_search" data-lv-label="homepage_hero">Search Tutors</button>
                                </div>

                                <div class="lvalues-popular-tags">
                                    <span>Popular:</span>
                                    <button type="button" data-mode="tutor" data-value="Math tutor">Math tutor</button>
                                    <button type="button" data-mode="tutor" data-value="English tutor">English tutor</button>
                                    <button type="button" data-mode="course" data-value="Physics">Physics</button>
                                    <button type="button" data-mode="course" data-value="Computer Science">Computer Science</button>
                                    <button type="button" data-mode="course" data-value="Google Cloud">Google Cloud</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row lvalues-proof-grid">
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="lvalues-proof-card">
                    <div class="lvalues-proof-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    <h5>Learner Support</h5>
                    <p>Students can search tutors, join batches, attend sessions, and track progress.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="lvalues-proof-card">
                    <div class="lvalues-proof-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <h5>Tutor Tools</h5>
                    <p>Tutors can find students, manage batches, schedule classes, and assign work.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="lvalues-proof-card">
                    <div class="lvalues-proof-icon"><i class="fa-solid fa-video"></i></div>
                    <h5>Live Sessions</h5>
                    <p>Support for online classes, offline learning, and hybrid learning plans.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="lvalues-proof-card">
                    <div class="lvalues-proof-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h5>Progress Tracking</h5>
                    <p>Assignments, tests, batch status, invites, and learning progress in one place.</p>
                </div>
            </div>
        </div>

        <div class="lvalues-results-section" id="lvaluesStudentResults">
            <div class="row align-items-end mb-3">
                <div class="col-lg-7">
                    <span class="lvalues-result-kicker">Student Results</span>
                    <h2>Learning support that feels clear to students and parents</h2>
                    <p>Use tutor matching, structured batches, mock tests, assignments, and progress reports to make learning easier to follow.</p>
                </div>
                <div class="col-lg-5 mt-3 mt-lg-0">
                    <p>Results vary by learner, but the platform is designed around visible next steps: what to study, who can help, and how progress is moving.</p>
                </div>
            </div>
            <div class="row g-3">
                <div class="col-lg-4 col-md-6">
                    <div class="lvalues-result-card"><b>School support</b><span>Students can find subject tutors by class, mode, and location, then continue through batches, sessions, assignments, and tests.</span></div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="lvalues-result-card"><b>Mock-test readiness</b><span>Free mock tests give immediate results and help learners identify weak topics before planning their next study session.</span></div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="lvalues-result-card"><b>Parent visibility</b><span>Parents can review tutor fit, learning mode, fees, and student progress signals before choosing ongoing support.</span></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('lvaluesHeroSearchForm');
            var searchPanel = document.querySelector('.lvalues-search-panel');
            if (!form) return;

            var modeInput = document.getElementById('lvaluesSearchFor');
            var queryInput = document.getElementById('lvaluesHeroQuery');
            var modeButtons = form.querySelectorAll('.lvalues-mode-buttons button');
            var tagButtons = form.querySelectorAll('.lvalues-popular-tags button');
            var title = document.getElementById('lvaluesSearchTitle');
            var description = document.getElementById('lvaluesSearchDescription');
            var openSearchButtons = document.querySelectorAll('.lvalues-open-search');

            function showSearchPanel() {
                if (searchPanel) {
                    searchPanel.className = searchPanel.className.replace(/\bis-visible\b/g, '').replace(/\s+$/,'') + ' is-visible';
                }
                var target = document.getElementById('lvaluesStartSearch');
                if (target && target.scrollIntoView) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            function setMode(mode) {
                if (!modeInput || !queryInput) return;

                for (var i = 0; i < modeButtons.length; i++) {
                    if (modeButtons[i].getAttribute('data-mode') === mode) {
                        modeButtons[i].className = 'btn active';
                    } else {
                        modeButtons[i].className = 'btn';
                    }
                }

                if (mode === 'course') {
                    modeInput.value = 'course';
                    form.action = '<?php echo site_url('home/courses'); ?>';
                    queryInput.placeholder = 'Course, e.g. Google Cloud, Python, Physics';
                    if (title) title.innerHTML = 'Find a course';
                    if (description) description.innerHTML = 'Search courses for school learning, IT skills, cloud, data science, professional training, and more.';
                } else {
                    modeInput.value = 'tutor';
                    form.action = '<?php echo site_url('home/search'); ?>';
                    queryInput.placeholder = 'Subject, e.g. Math, English, Java';
                    if (title) title.innerHTML = 'Find a tutor';
                    if (description) description.innerHTML = 'Search tutors by subject, class, skill, location, online classes, offline tuition, or hybrid learning.';
                }
            }

            for (var i = 0; i < modeButtons.length; i++) {
                modeButtons[i].onclick = function () {
                    setMode(this.getAttribute('data-mode'));
                };
            }

            for (var j = 0; j < tagButtons.length; j++) {
                tagButtons[j].onclick = function () {
                    setMode(this.getAttribute('data-mode'));
                    queryInput.value = this.getAttribute('data-value');
                    queryInput.focus();
                };
            }

            for (var k = 0; k < openSearchButtons.length; k++) {
                openSearchButtons[k].onclick = function (e) {
                    if (e && e.preventDefault) e.preventDefault();
                    setMode(this.getAttribute('data-mode'));
                    showSearchPanel();
                    return false;
                };
            }

            setMode('tutor');
        });
    </script>
</section>
<!---------- Banner Section End ---------------->

<?php if (!empty($latest_public_exams)): ?>
<section class="lvalues-mocktest-section" id="lvaluesMockTests">
    <style>
        .lvalues-mocktest-section{background:#ffffff;padding:58px 0;border-bottom:1px solid #eef0f7;}
        .lvalues-mocktest-wrap{background:linear-gradient(135deg,#f7f9ff 0%,#fff 52%,#f4f0ff 100%);border:1px solid #e9ecf5;border-radius:26px;padding:34px;box-shadow:0 20px 55px rgba(34,41,87,.08);}
        .lvalues-mocktest-head{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin-bottom:22px;}
        .lvalues-mocktest-kicker{display:inline-block;background:#ecfdf5;color:#047857;border:1px solid #bbf7d0;border-radius:999px;padding:7px 12px;font-weight:800;font-size:13px;margin-bottom:10px;}
        .lvalues-mocktest-head h2{margin:0;color:#17213a;font-size:32px;line-height:1.25;font-weight:900;letter-spacing:-.5px;}
        .lvalues-mocktest-head p{margin:10px 0 0;color:#65708a;line-height:1.7;max-width:720px;}
        .lvalues-mocktest-viewall{white-space:nowrap;border-radius:12px!important;font-weight:800;}
        .lvalues-mock-card{height:100%;background:#fff;border:1px solid #e7eaf3;border-radius:20px;padding:20px;box-shadow:0 12px 30px rgba(34,41,87,.06);transition:transform .2s ease,box-shadow .2s ease;}
        .lvalues-mock-card:hover{transform:translateY(-3px);box-shadow:0 18px 42px rgba(34,41,87,.12);}
        .lvalues-mock-card h3{margin:10px 0 8px;color:#17213a;font-size:20px;font-weight:900;line-height:1.25;}
        .lvalues-mock-card p{color:#65708a;font-size:14px;line-height:1.6;min-height:44px;margin-bottom:12px;}
        .lvalues-mock-meta{display:flex;flex-wrap:wrap;gap:7px;margin:10px 0 16px;}
        .lvalues-mock-pill{display:inline-block;border:1px solid #e5e7eb;border-radius:999px;background:#f8fafc;color:#536078;padding:6px 9px;font-size:12px;font-weight:800;}
        .lvalues-free-badge{display:inline-block;border-radius:999px;background:#10b981;color:#fff;padding:6px 10px;font-weight:900;font-size:12px;}
        .lvalues-mock-start{display:block;text-align:center;border-radius:13px;background:#6c4df6;color:#fff!important;padding:12px 14px;font-weight:900;text-decoration:none;}
        .lvalues-mock-start:hover{background:#5639de;text-decoration:none;}
        @media(max-width:767px){.lvalues-mocktest-wrap{padding:22px}.lvalues-mocktest-head{display:block}.lvalues-mocktest-viewall{margin-top:14px}.lvalues-mocktest-head h2{font-size:26px}}
    </style>
    <div class="container">
        <div class="lvalues-mocktest-wrap">
            <div class="lvalues-mocktest-head">
                <div>
                    <span class="lvalues-mocktest-kicker">Free Mock Tests</span>
                    <h2>Practice Mock Tests & Competitive Exams</h2>
                    <p>Attempt public exams in a final-exam style experience with timer, instant result, feedback and weak area report.</p>
                </div>
                <a href="<?php echo site_url('mock-tests'); ?>" class="btn btn-outline-primary lvalues-btn-outline lvalues-mocktest-viewall">View All Mock Tests</a>
            </div>
            <div class="row g-3">
                <?php foreach ($latest_public_exams as $exam): ?>
                    <?php
                        $exam_title = (string)($exam['title'] ?? 'Mock Test');
                        $description = trim(strip_tags((string)($exam['description'] ?? '')));
                        if (strlen($description) > 105) $description = substr($description, 0, 102) . '...';
                        if ($description === '') $description = 'Practice this exam with timer, instant result and weak area analysis.';
                        $question_count = (int)($exam['question_count'] ?: ($exam['question_limit'] ?? 0));
                    ?>
                    <div class="col-lg-4 col-md-6 mb-3">
                        <article class="lvalues-mock-card">
                            <span class="lvalues-free-badge">Free</span>
                            <h3><?php echo html_escape($exam_title); ?></h3>
                            <p><?php echo html_escape($description); ?></p>
                            <div class="lvalues-mock-meta">
                                <span class="lvalues-mock-pill"><?php echo (int)($exam['time_limit_minutes'] ?? 0); ?> min</span>
                                <span class="lvalues-mock-pill"><?php echo $question_count; ?> questions</span>
                                <span class="lvalues-mock-pill"><?php echo html_escape($exam['difficulty'] ?? 'Beginner'); ?></span>
                                <span class="lvalues-mock-pill"><?php echo (int)($exam['attempt_count'] ?? 0); ?> attempts</span>
                            </div>
                            <a class="lvalues-mock-start" href="<?php echo site_url('mock-tests/' . ($exam['slug'] ?? '')); ?>">Start Test</a>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="lvalues-audience-section" id="lvaluesAudiencePaths">
    <div class="container">
        <div class="lvalues-section-heading">
            <h2>Choose the right Lvalues path</h2>
            <p>Clear journeys for school students, parents, tutors, working professionals, and companies looking for structured training.</p>
        </div>
        <div class="row g-3">
            <div class="col-lg col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    <h3>Students</h3>
                    <p>Find courses, tutors, live classes, assignments, tests, and progress support for school and skill learning.</p>
                    <a href="<?php echo site_url('sign_up'); ?>">Start learning</a>
                </div>
            </div>
            <div class="col-lg col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-people-roof"></i></div>
                    <h3>Parents</h3>
                    <p>Compare tutor fit, learning mode, class level, and progress signals before choosing support for your child.</p>
                    <a href="<?php echo site_url('home/search?search_for=tutor'); ?>">Find trusted tutors</a>
                </div>
            </div>
            <div class="col-lg col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <h3>Tutors</h3>
                    <p>Create a tutor profile, manage batches, invite students, schedule sessions, and grow your teaching business.</p>
                    <a href="<?php echo site_url('sign_up?instructor=yes'); ?>">Become a tutor</a>
                </div>
            </div>
            <div class="col-lg col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-laptop-code"></i></div>
                    <h3>Professionals</h3>
                    <p>Build practical skills in IT, cloud, data, programming, and career-focused professional courses.</p>
                    <a href="<?php echo site_url('home/courses?query=IT%20Training'); ?>">Explore IT training</a>
                </div>
            </div>
            <div class="col-lg col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-building"></i></div>
                    <h3>Corporate Training</h3>
                    <p>Plan team learning for onboarding, upskilling, technology adoption, and role-based training programs.</p>
                    <a href="<?php echo site_url('home/contact_us?type=corporate'); ?>">Request a proposal</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lvalues-work-section" id="lvaluesHowItWorks">
    <div class="container">
        <div class="lvalues-section-heading">
            <h2>How Lvalues Works</h2>
            <p>From discovery to progress tracking, Lvalues brings course learning and tutor-led learning into one guided flow.</p>
        </div>
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-work-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h3>Discover</h3>
                    <p>Search courses or tutors by subject, skill, class level, mode, and learning goal.</p>
                    <a href="#lvaluesStartSearch" class="lvalues-open-search" data-mode="course">Start search</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-work-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-star"></i></div>
                    <h3>Compare</h3>
                    <p>Review ratings, outcomes, tutor profiles, course detail, pricing, and delivery mode.</p>
                    <a href="<?php echo site_url('home/courses'); ?>">Browse catalog</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-work-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-video"></i></div>
                    <h3>Learn</h3>
                    <p>Join online, offline, or hybrid sessions with assignments, tests, and course materials.</p>
                    <a href="<?php echo site_url('sign_up'); ?>">Create account</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-work-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>Track outcomes</h3>
                    <p>Use dashboards, batch status, invites, tests, and progress records to stay accountable.</p>
                    <a href="<?php echo site_url('login'); ?>">Go to dashboard</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lvalues-outcome-strip" id="lvaluesOutcomes">
    <div class="container">
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-outcome-card">
                    <h3>Verified tutor quality</h3>
                    <p>Tutor applications support document review, teaching mode, categories, fees, and location context.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-outcome-card">
                    <h3>Outcome-led learning</h3>
                    <p>Courses and batches can communicate lessons, ratings, reviews, outcomes, and progress checkpoints.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="lvalues-outcome-card">
                    <h3>Parent confidence</h3>
                    <p>Families can see courses, tutors, learning modes, and student progress flows before committing.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6" id="lvalues-ai-assistant">
                <div class="lvalues-outcome-card">
                    <h3>AI learning assistant</h3>
                    <p>Use guided support for course discovery, tutor matching, study planning, and support questions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="lvalues-corporate-band" id="lvaluesCorporateTraining">
    <div class="container">
        <div class="lvalues-corporate-panel">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2>Corporate training and career programs</h2>
                    <p>Build structured learning paths for teams, professionals, and career-focused learners across IT, cloud, programming, data, communication, and role-specific skills.</p>
                </div>
                <div class="col-lg-4">
                    <div class="lvalues-corporate-actions">
                        <a href="<?php echo site_url('home/contact_us?type=corporate'); ?>" class="btn btn-primary lvalues-btn-primary">Talk to Lvalues</a>
                        <a href="<?php echo site_url('home/courses?query=career'); ?>" class="btn btn-outline-primary lvalues-btn-outline">View programs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$CI =& get_instance();
if (!isset($CI->sponsored_course_model) || !is_object($CI->sponsored_course_model)) {
    $CI->load->model('Sponsored_course_model', 'sponsored_course_model');
}
$sponsored_courses = $CI->sponsored_course_model->get_published(6);
?>
<?php if (!empty($sponsored_courses)): ?>
<section class="lvalues-work-section" id="lvaluesSponsoredCourses">
    <div class="container">
        <div class="lvalues-section-heading">
            <h2>Sponsored Courses</h2>
            <p>Featured third-party learning programs curated for Lvalues learners.</p>
        </div>
        <div class="row g-3">
            <?php foreach ($sponsored_courses as $sponsored): ?>
                <?php
                    $registration_link = trim((string)($sponsored['registration_link'] ?? ''));
                    $safe_link = $registration_link !== '' ? $registration_link : site_url('home/contact_us');
                    $banner = !empty($sponsored['banner_image']) ? base_url($sponsored['banner_image']) : base_url('uploads/system/home_1.png');
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="lvalues-audience-card h-100">
                        <div style="aspect-ratio: 16 / 9; overflow: hidden; border-radius: 8px; margin-bottom: 14px; background: #eef2f7;">
                            <img src="<?php echo html_escape($banner); ?>" alt="<?php echo html_escape($sponsored['course_title']); ?> sponsored course banner" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-light text-dark"><?php echo ucfirst(html_escape($sponsored['mode'])); ?></span>
                            <?php if ($sponsored['fees'] !== null && $sponsored['fees'] !== ''): ?>
                                <strong>₹<?php echo number_format((float)$sponsored['fees'], 2); ?></strong>
                            <?php endif; ?>
                        </div>
                        <h3><?php echo html_escape($sponsored['course_title']); ?></h3>
                        <p class="mb-2"><strong><?php echo html_escape($sponsored['provider_name']); ?></strong></p>
                        <?php if (!empty($sponsored['course_description'])): ?>
                            <p><?php echo html_escape(ellipsis(strip_tags($sponsored['course_description']), 120)); ?></p>
                        <?php endif; ?>
                        <p class="text-muted small mb-2">
                            <?php if (!empty($sponsored['class_timing'])): ?>
                                <?php echo html_escape($sponsored['class_timing']); ?>
                            <?php endif; ?>
                            <?php if (!empty($sponsored['location'])): ?>
                                <br><?php echo html_escape($sponsored['location']); ?>
                            <?php endif; ?>
                        </p>
                        <a href="<?php echo html_escape($safe_link); ?>" target="_blank" rel="noopener noreferrer">Register Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="lvalues-corporate-band" id="lvaluesPricing">
    <div class="container">
        <div class="lvalues-section-heading">
            <h2>Transparent learning options</h2>
            <p>Explore free and paid courses, tutor-led batches, per-hour tutor fees, and custom corporate training proposals before choosing a learning path.</p>
        </div>
        <div class="row g-3">
            <div class="col-lg-4 col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-book-open"></i></div>
                    <h3>Courses</h3>
                    <p>Compare course ratings, lesson depth, pricing, outcomes, and instructor details from the catalog.</p>
                    <a href="<?php echo site_url('home/courses'); ?>">Browse courses</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                    <h3>Tutor learning</h3>
                    <p>Find tutors by subject, learning mode, location context, teaching category, and fee structure.</p>
                    <a href="<?php echo site_url('home/search?search_for=tutor'); ?>">Find tutors</a>
                </div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="lvalues-audience-card">
                    <div class="lvalues-card-icon"><i class="fa-solid fa-building"></i></div>
                    <h3>Team programs</h3>
                    <p>Request custom pricing for onboarding, upskilling, role-based training, or corporate cohorts.</p>
                    <a href="<?php echo site_url('home/contact_us?type=corporate'); ?>">Request pricing</a>
                </div>
            </div>
        </div>
    </div>
</section>


<?php if(get_frontend_settings('upcoming_course_section') == 1): ?>
<!-- Start Upcoming Courses -->
<?php $upcoming_courses = $this->db->order_by('id', 'desc')->limit(6)->get_where('course', ['status' => 'upcoming']); ?>
<?php if($upcoming_courses->num_rows() > 0): ?>
    <section class="py-5">
      <div class="container">
        <div class="row">
          <div class="col-lg-4">
            <div class="title-one pb-20">
              <p class="subtitle text-uppercase"><?php echo get_phrase('Upcoming'); ?></p>
              <h4 class="title"><?php echo get_phrase('Upcoming courses'); ?></h4>
              <div class="bar"></div>
            </div>
            <p class="fz_15_m_24"><?php echo get_phrase('Discover a world of learning opportunities through our upcoming courses, where industry experts and thought leaders will guide you in acquiring new expertise, expanding your horizons, and reaching your full potential.') ?></p>
          </div>
          <div class="col-lg-8">
            <!-- Items -->
            <div class="row g-3">
              <?php
                foreach($upcoming_courses->result_array() as $upcoming_course):
                ?>
                <div class="col-lg-4">
                  <a href="<?php echo site_url('home/course/' . rawurlencode(slugify($upcoming_course['title'])) . '/' . $upcoming_course['id']); ?>" id="top_course_<?php echo $upcoming_course['id']; ?>" class="course-item-one">
                    <div class="img-rating">
                      <div class="img"><img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($upcoming_course['id']); ?>" alt="" /></div>
                    </div>
                    <div class="content">
                      <h4 class="title"><?php echo $upcoming_course['title']; ?></h4>
                      <p class="info ellipsis-line-2 fw-400"><?php echo $upcoming_course['short_description']; ?></p>
                    </div>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </section>
<?php endif; ?>
<!-- End Upcoming Courses -->
<?php endif; ?>


<?php if(get_frontend_settings('top_course_section') == 1): ?>
<!---------- Top courses Section start --------------->
<section class="courses grid-view-body py-5">
    <div class="container">
        <h2 class="pt-0"><span><?php echo site_phrase('top_courses'); ?></span></h2>
        <p><?php echo site_phrase('These_are_the_most_popular_courses_among_Listen_Courses_learners_worldwide')?></p>
        <div class="courses-card">
            <div class="course-group-slider">
                <?php
                $top_courses = $this->crud_model->get_top_courses()->result_array();
                foreach ($top_courses as $top_course) :
                    $lessons = $this->crud_model->get_lessons('course', $top_course['id']);
                    $instructor_details = $this->user_model->get_all_user($top_course['creator'])->row_array();
                    $course_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($top_course['id']);
                    $total_rating =  $this->crud_model->get_ratings('course', $top_course['id'], true)->row()->rating;
                    $number_of_ratings = $this->crud_model->get_ratings('course', $top_course['id'])->num_rows();
                    if ($number_of_ratings > 0) {
                        $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                    } else {
                        $average_ceil_rating = 0;
                    }
                    ?>
                    <div class="single-popup-course">
                        <a href="<?php echo site_url('home/course/' . rawurlencode(slugify($top_course['title'])) . '/' . $top_course['id']); ?>" id="top_course_<?php echo $top_course['id']; ?>" class="checkPropagation courses-card-body">
                            <div class="courses-card-image">
                                <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($top_course['id']); ?>" alt="<?php echo html_escape($top_course['title']); ?>">
                                <div class="courses-icon <?php if(in_array($top_course['id'], $my_wishlist_items)) echo 'red-heart'; ?>" id="coursesWishlistIconTopCourse<?php echo $top_course['id']; ?>">
                                    <i class="fa-solid fa-heart checkPropagation" onclick="actionTo('<?php echo site_url('home/toggleWishlistItems/'.$top_course['id'].'/TopCourse'); ?>')"></i>
                                </div>
                                <div class="courses-card-image-text">
                                    <h3><?php echo get_phrase($top_course['level']); ?></h3>
                                </div> 
                            </div>
                            <div class="courses-text">
                                <h5 class="mb-2"><?php echo $top_course['title']; ?></h5>
                                <div class="review-icon">
                                    <div class="review-icon-star align-items-center">
                                        <p><?php echo $average_ceil_rating; ?></p>
                                        <p><i class="fa-solid fa-star <?php if($number_of_ratings > 0) echo 'filled'; ?>"></i></p>
                                        <p>(<?php echo $number_of_ratings; ?> <?php echo get_phrase('Reviews') ?>)</p>
                                    </div>
                                    <div class="review-btn d-flex align-items-center">
                                       <span class="compare-img checkPropagation" onclick="redirectTo('<?php echo base_url('home/compare?course-1='.slugify($top_course['title']).'&course-id-1='.$top_course['id']); ?>');">
                                            <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/compare.png') ?>" alt="<?php echo get_phrase('Compare course'); ?>">
                                            <?php echo get_phrase('Compare'); ?>
                                        </span>
                                    </div>
                                </div>
                                <p class="ellipsis-line-2"><?php echo $top_course['short_description'] ?></p>
                                <div class="courses-price-border">
                                    <div class="courses-price">
                                        <div class="courses-price-left">
                                            <?php if($top_course['is_free_course']): ?>
                                                <h5><?php echo get_phrase('Free'); ?></h5>
                                            <?php elseif($top_course['discount_flag']): ?>
                                                <h5><?php echo currency($top_course['discounted_price']); ?></h5>
                                                <p class="mt-1"><del><?php echo currency($top_course['price']); ?></del></p>
                                            <?php else: ?>
                                                <h5><?php echo currency($top_course['price']); ?></h5>
                                            <?php endif; ?>
                                        </div>
                                        <div class="courses-price-right ">
                                            <?php if($course_duration): ?>
                                                <p class="m-0"> <i class="fa-regular fa-clock p-0 text-15px"></i> <?php echo $course_duration; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                             </div>
                        </a>




                        <div id="top_course_feature_<?php echo $top_course['id']; ?>" class="course-popover-content">
                            <?php if ($top_course['last_modified'] == "") : ?>
                                <p class="last-update"><?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $top_course['date_added']); ?></p>
                            <?php else : ?>
                                <p class="last-update"><?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $top_course['last_modified']); ?></p>
                            <?php endif; ?>
                            <div class="course-title">
                                 <a href="<?php echo site_url('home/course/' . rawurlencode(slugify($top_course['title'])) . '/' . $top_course['id']); ?>"><?php echo $top_course['title']; ?></a>
                            </div>
                            <div class="course-meta">
                                <?php if ($top_course['course_type'] == 'general') : ?>
                                    <span class=""><i class="fas fa-play-circle"></i>
                                        <?php echo $this->crud_model->get_lessons('course', $top_course['id'])->num_rows() . ' ' . site_phrase('lessons'); ?>
                                    </span>
                                    <?php if($course_duration): ?>
                                        <span class=""><i class="far fa-clock"></i>
                                            <?php echo $course_duration; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php elseif ($top_course['course_type'] == 'h5p') : ?>
                                    <span class="badge bg-light"><?= site_phrase('h5p_course'); ?></span>
                                <?php elseif ($top_course['course_type'] == 'scorm') : ?>
                                    <span class="badge bg-light"><?= site_phrase('scorm_course'); ?></span>
                                <?php endif; ?>
                                <span class=""><i class="fas fa-closed-captioning"></i><?php echo ucfirst($top_course['language']); ?></span>
                             </div>
                            <div class="course-subtitle">
                                 <?php echo $top_course['short_description']; ?>
                            </div>
                            <h6 class="text-black text-14px mb-1"><?php echo get_phrase('Outcomes') ?>:</h6>
                            <ul class="will-learn">
                                <?php $outcomes = json_decode($top_course['outcomes']);
                                foreach ($outcomes as $outcome) : ?>
                                    <li><?php echo $outcome; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="popover-btns">
                                <?php $cart_items = $this->session->userdata('cart_items'); ?>
                                <?php if(is_purchased($top_course['id'])): ?>
                                    <a href="<?php echo site_url('home/lesson/'.slugify($top_course['title']).'/'.$top_course['id']) ?>" class="purchase-btn d-flex align-items-center  me-auto"><i class="far fa-play-circle me-2"></i> <?php echo get_phrase('Start Now'); ?></a>
                                    <?php if ($top_course['is_free_course'] != 1) : ?>
                                        <button type="button" class="gift-btn ms-auto" title="<?php echo get_phrase('Gift someone else'); ?>" data-bs-toggle="tooltip" onclick="actionTo('<?php echo site_url('home/handle_buy_now/' . $top_course['id'].'?gift=1'); ?>')"><i class="fas fa-gift"></i></button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($top_course['is_free_course'] == 1) : ?>
                                        <a class="purchase-btn green_purchase ms-auto" href="<?php echo site_url('home/get_enrolled_to_free_course/' . $top_course['id']); ?>"><?php echo get_phrase('Enroll Now'); ?></a>
                                    <?php else : ?>

                                        <!-- Cart button -->
                                        <a id="added_to_cart_btn_top_course<?php echo $top_course['id']; ?>" class="purchase-btn align-items-center me-auto <?php if(!in_array($top_course['id'], $cart_items)) echo 'd-hidden'; ?>" href="javascript:void(0)" onclick="actionTo('<?php echo site_url('home/handle_cart_items/' . $top_course['id'].'/top_course'); ?>');">
                                            <i class="fas fa-minus me-2"></i> <?php echo get_phrase('Remove from cart'); ?>
                                        </a>
                                        <a id="add_to_cart_btn_top_course<?php echo $top_course['id']; ?>" class="purchase-btn align-items-center me-auto <?php if(in_array($top_course['id'], $cart_items)) echo 'd-hidden'; ?>" href="javascript:void(0)" onclick="actionTo('<?php echo site_url('home/handle_cart_items/' . $top_course['id'].'/top_course'); ?>'); ">
                                            <i class="fas fa-plus me-2"></i> <?php echo get_phrase('Add to cart'); ?>
                                        </a>
                                        <!-- Cart button ended-->
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <script>
                                $(document).ready(function(){
                                    $('#top_course_<?php echo $top_course['id']; ?>').webuiPopover({
                                        url:'#top_course_feature_<?php echo $top_course['id']; ?>',
                                        trigger:'hover',
                                        animation:'pop',
                                        cache:false,
                                        multi:true,
                                        direction:'rtl', 
                                        placement:'horizontal',
                                    });
                                });
                            </script>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<!---------- Top courses Section End --------------->
<?php endif; ?>

<?php if(get_frontend_settings('top_category_section') == 1): ?>
<!---------- Top Categories Start ------------->
<section class="top-categories py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-6">
                <h2 class="text-center"><?php echo site_phrase('top_categories'); ?></h2>
                <p class="text-center mt-4"><?php echo site_phrase('These_are_the_most_popular_courses_among_Listen_Courses_learners_worldwide')?></p>
            </div>
            <div class="col-lg-3"></div>
        </div>
        <div class="category-product mt-5">
            <div class="row justify-content-center">
                <?php
                    $top_10_categories = $this->crud_model->get_top_categories(12, 'sub_category_id');
                    $sorted_top_categories = [];
                    foreach ($top_10_categories as $top_10_category) {
                        $category_details = $this->crud_model->get_category_details_by_id($top_10_category['sub_category_id'])->row_array();
                        if (!empty($category_details)) {
                            $top_10_category['category_details'] = $category_details;
                            $sorted_top_categories[] = $top_10_category;
                        }
                    }
                    usort($sorted_top_categories, function ($left, $right) {
                        $left_name = trim((string)($left['category_details']['name'] ?? ''));
                        $right_name = trim((string)($right['category_details']['name'] ?? ''));
                        $left_is_class = preg_match('/^class\s*(\d+)$/i', $left_name, $left_match);
                        $right_is_class = preg_match('/^class\s*(\d+)$/i', $right_name, $right_match);
                        if ($left_is_class && $right_is_class) {
                            return (int)$left_match[1] <=> (int)$right_match[1];
                        }
                        if ($left_is_class) {
                            return -1;
                        }
                        if ($right_is_class) {
                            return 1;
                        }
                        return strnatcasecmp($left_name, $right_name);
                    });
                ?>
                <?php foreach($sorted_top_categories as $top_10_category): ?>
                <?php $category_details = $top_10_category['category_details']; ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 col-6">
                        <a href="<?php echo site_url('home/courses?category='.$category_details['slug']); ?>" class="category-product-body position-relative">
                           <div class="cate-icon"  style="color: #<?php echo rand(100000, 999999); ?>">
                                <i class="<?php echo $category_details['font_awesome_class']; ?>"></i>
                            </div>
                            <span class="category-hide-icon"><i class="fa-solid fa-angle-right"></i></span>
                            <h5 class="pt-0"> <?php echo $category_details['name']; ?></h5>
                            <p class="hide-cat-text"><?php echo $top_10_category['course_number'].' '.site_phrase('courses'); ?></p>
                         </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<!---------- Top Categories end ------------->
<?php endif; ?>

<?php if(get_frontend_settings('latest_course_section') == 1): ?>
<!---------- Latest courses Section start --------------->
<section class="courses grid-view-body py-5">
    <div class="container">
        <h2 class="text-center pt-0"><span><?php echo site_phrase('top') . ' 10 ' . site_phrase('latest_courses'); ?></span></h2>
        <p class="text-center"><?php echo site_phrase('These_are_the_most_latest_courses_among_Listen_Courses_learners_worldwide')?></p>
        <div class="courses-card">
            <div class="course-group-slider ">
                <?php
                $latest_courses = $this->crud_model->get_latest_10_course();
                foreach ($latest_courses as $latest_course) :
                    $lessons = $this->crud_model->get_lessons('course', $latest_course['id']);
                    $instructor_details = $this->user_model->get_all_user($latest_course['creator'])->row_array();
                    $course_duration = $this->crud_model->get_total_duration_of_lesson_by_course_id($latest_course['id']);
                    $total_rating =  $this->crud_model->get_ratings('course', $latest_course['id'], true)->row()->rating;
                    $number_of_ratings = $this->crud_model->get_ratings('course', $latest_course['id'])->num_rows();
                    if ($number_of_ratings > 0) {
                        $average_ceil_rating = ceil($total_rating / $number_of_ratings);
                    } else {
                        $average_ceil_rating = 0;
                    }
                    ?>
                    <div class="single-popup-course">
                        <a href="<?php echo site_url('home/course/' . rawurlencode(slugify($latest_course['title'])) . '/' . $latest_course['id']); ?>" id="latest_course_<?php echo $latest_course['id']; ?>" class="checkPropagation courses-card-body">
                            <div class="courses-card-image">
                                <img loading="lazy" src="<?php echo $this->crud_model->get_course_thumbnail_url($latest_course['id']); ?>" alt="<?php echo html_escape($latest_course['title']); ?>">
                                <div class="courses-icon <?php if(in_array($latest_course['id'], $my_wishlist_items)) echo 'red-heart'; ?>" id="coursesWishlistIconLatestCourse<?php echo $latest_course['id']; ?>">
                                    <i class="fa-solid fa-heart checkPropagation" onclick="actionTo('<?php echo site_url('home/toggleWishlistItems/'.$latest_course['id'].'/LatestCourse'); ?>')"></i>
                                </div>
                                <div class="courses-card-image-text">
                                    <h3><?php echo get_phrase($latest_course['level']); ?></h3>
                                </div> 
                            </div>
                            <div class="courses-text">
                                <h5 class="mb-2"><?php echo $latest_course['title']; ?></h5>
                                <div class="review-icon">
                                    <div class="review-icon-star align-items-center">
                                        <p><?php echo $average_ceil_rating; ?></p>
                                        <p><i class="fa-solid fa-star <?php if($number_of_ratings > 0) echo 'filled'; ?>"></i></p>
                                        <p>(<?php echo $number_of_ratings; ?> <?php echo get_phrase('Reviews') ?>)</p>
                                    </div>
                                    <div class="review-btn d-flex align-items-center">
                                       <span class="compare-img checkPropagation" onclick="redirectTo('<?php echo base_url('home/compare?course-1='.slugify($latest_course['title']).'&course-id-1='.$latest_course['id']); ?>');">
                                            <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/compare.png') ?>" alt="<?php echo get_phrase('Compare course'); ?>">
                                            <?php echo get_phrase('Compare'); ?>
                                        </span>
                                    </div>
                                </div>
                                <p class="ellipsis-line-2"><?php echo $latest_course['short_description'] ?></p>
                                <div class="courses-price-border">
                                    <div class="courses-price">
                                        <div class="courses-price-left">
                                            <?php if($latest_course['is_free_course']): ?>
                                                <h5><?php echo get_phrase('Free'); ?></h5>
                                            <?php elseif($latest_course['discount_flag']): ?>
                                                <h5><?php echo currency($latest_course['discounted_price']); ?></h5>
                                                <p class="mt-1"><del><?php echo currency($latest_course['price']); ?></del></p>
                                            <?php else: ?>
                                                <h5><?php echo currency($latest_course['price']); ?></h5>
                                            <?php endif; ?>
                                        </div>
                                        <div class="courses-price-right ">
                                            <?php if($course_duration): ?>
                                                <p class="m-0"><i class="fa-regular fa-clock p-0 text-15px"></i> <?php echo $course_duration; ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                             </div>
                        </a>




                        <div id="latest_course_feature_<?php echo $latest_course['id']; ?>" class="course-popover-content">
                            <?php if ($latest_course['last_modified'] == "") : ?>
                                <p class="last-update"><?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $latest_course['date_added']); ?></p>
                            <?php else : ?>
                                <p class="last-update"><?php echo site_phrase('last_updated') . ' ' . date('D, d-M-Y', $latest_course['last_modified']); ?></p>
                            <?php endif; ?>
                            <div class="course-title">
                                 <a href="<?php echo site_url('home/course/' . rawurlencode(slugify($latest_course['title'])) . '/' . $latest_course['id']); ?>"><?php echo $latest_course['title']; ?></a>
                            </div>
                            <div class="course-meta">
                                <?php if ($latest_course['course_type'] == 'general') : ?>
                                    <span class=""><i class="fas fa-play-circle"></i>
                                        <?php echo $this->crud_model->get_lessons('course', $latest_course['id'])->num_rows() . ' ' . site_phrase('lessons'); ?>
                                    </span>
                                    <?php if($course_duration): ?>
                                        <span class=""><i class="far fa-clock"></i>
                                            <?php echo $course_duration; ?>
                                        </span>
                                    <?php endif; ?>
                                <?php elseif ($latest_course['course_type'] == 'h5p') : ?>
                                    <span class="badge bg-light"><?= site_phrase('h5p_course'); ?></span>
                                <?php elseif ($latest_course['course_type'] == 'scorm') : ?>
                                    <span class="badge bg-light"><?= site_phrase('scorm_course'); ?></span>
                                <?php endif; ?>
                                <span class=""><i class="fas fa-closed-captioning"></i><?php echo ucfirst($latest_course['language']); ?></span>
                             </div>
                            <div class="course-subtitle">
                                 <?php echo $latest_course['short_description']; ?>
                            </div>
                            <h6 class="text-black text-14px mb-1"><?php echo get_phrase('Outcomes') ?>:</h6>
                            <ul class="will-learn">
                                <?php $outcomes = json_decode($latest_course['outcomes']);
                                foreach ($outcomes as $outcome) : ?>
                                    <li><?php echo $outcome; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="popover-btns">
                                <?php $cart_items = $this->session->userdata('cart_items'); ?>
                                <?php if(is_purchased($latest_course['id'])): ?>
                                    <a href="<?php echo site_url('home/lesson/'.slugify($latest_course['title']).'/'.$latest_course['id']) ?>" class="purchase-btn d-flex align-items-center  me-auto"><i class="far fa-play-circle me-2"></i> <?php echo get_phrase('Start Now'); ?></a>
                                    <?php if ($latest_course['is_free_course'] != 1) : ?>
                                        <button type="button" class="gift-btn ms-auto" title="<?php echo get_phrase('Gift someone else'); ?>" data-bs-toggle="tooltip" onclick="actionTo('<?php echo site_url('home/handle_buy_now/' . $latest_course['id'].'?gift=1'); ?>')"><i class="fas fa-gift"></i></button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($latest_course['is_free_course'] == 1) : ?>
                                        <a class="purchase-btn green_purchase ms-auto" href="<?php echo site_url('home/get_enrolled_to_free_course/' . $latest_course['id']); ?>"><?php echo get_phrase('Enroll Now'); ?></a>
                                    <?php else : ?>

                                        <!-- Cart button -->
                                        <a id="added_to_cart_btn_latest_course<?php echo $latest_course['id']; ?>" class="purchase-btn align-items-center me-auto <?php if(!in_array($latest_course['id'], $cart_items)) echo 'd-hidden'; ?>" href="javascript:void(0)" onclick="actionTo('<?php echo site_url('home/handle_cart_items/' . $latest_course['id'].'/latest_course'); ?>');">
                                            <i class="fas fa-minus me-2"></i> <?php echo get_phrase('Remove from cart'); ?>
                                        </a>
                                        <a id="add_to_cart_btn_latest_course<?php echo $latest_course['id']; ?>" class="purchase-btn align-items-center me-auto <?php if(in_array($latest_course['id'], $cart_items)) echo 'd-hidden'; ?>" href="javascript:void(0)" onclick="actionTo('<?php echo site_url('home/handle_cart_items/' . $latest_course['id'].'/latest_course'); ?>'); ">
                                            <i class="fas fa-plus me-2"></i> <?php echo get_phrase('Add to cart'); ?>
                                        </a>
                                        <!-- Cart button ended-->
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <script>
                                $(document).ready(function(){
                                    $('#latest_course_<?php echo $latest_course['id']; ?>').webuiPopover({
                                        url:'#latest_course_feature_<?php echo $latest_course['id']; ?>',
                                        trigger:'hover',
                                        animation:'pop',
                                        cache:false,
                                        multi:true,
                                        direction:'rtl', 
                                        placement:'horizontal',
                                    });
                                });
                            </script>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<!---------- Latest courses Section End --------------->
<?php endif; ?>


<?php if(get_frontend_settings('top_instructor_section') == 1): ?>
<!---------  Expert Instructor Start ---------------->
<?php $top_instructor_ids = $this->crud_model->get_top_instructor(10); ?>
<?php if(count($top_instructor_ids) > 0): ?>
<section class="expert-instructor top-categories py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-3"></div>
            <div class="col-lg-6">
                <h2 class="text-center mt-0 pt-0"><?php echo get_phrase('Top Instructors') ?></h2>
                <p class="text-center mt-4 mb-4"><?php echo get_phrase('They efficiently serve large number of students on our platform') ?></p>
            </div>
            <div class="col-lg-3 "></div>
        </div>
        <div class="instructor-card">
            <div class="row justify-content-center">
                <?php foreach($top_instructor_ids as $top_instructor_id):
                    $top_instructor = $this->user_model->get_all_user($top_instructor_id['creator'])->row_array();
                    $social_links  = json_decode($instructor_details['social_links'], true); ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 ">
                        <div class="instructor-card-body">
                            <div class="instructor-card-img">
                                <img loading="lazy" src="<?php echo $this->user_model->get_user_image_url($top_instructor['id']); ?>" alt="<?php echo html_escape(trim(($top_instructor['first_name'] ?? '') . ' ' . ($top_instructor['last_name'] ?? '')) ?: 'Lvalues instructor'); ?>">
                            </div>
                            <div class="instructor-card-text">
                                <div class="icon">
                                    <div class="icon-div-2">
                                        <?php if($social_links['facebook']): ?>
                                            <a class="" href="<?php echo $social_links['facebook']; ?>" target="_blank">
                                                <i class="fa-brands fa-facebook-f"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($social_links['twitter']): ?>
                                            <a class="" href="<?php echo $social_links['twitter']; ?>" target="_blank">
                                                <i class="fa-brands fa-twitter"></i>
                                            </a>
                                        <?php endif; ?>
                                        <?php if($social_links['linkedin']): ?>
                                            <a class="" href="<?php echo $social_links['linkedin']; ?>" target="_blank">
                                                <i class="fa-brands fa-linkedin"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a class="text-muted w-100" href="<?php echo site_url('home/instructor_page/'.$top_instructor['id']); ?>">
                                    <h3 class="text-center"><?php echo $top_instructor['first_name'].' '.$top_instructor['last_name']; ?></h3>
                                    <p class="ellipsis-line-2"><?php echo $top_instructor['title']; ?></p>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>

<?php if(get_frontend_settings('motivational_speech_section') == 1): ?>
<?php $motivational_speechs = json_decode(get_frontend_settings('motivational_speech'), true); ?>
<?php if(count($motivational_speechs) > 0): ?>
<!---------  Motivetional Speech Start ---------------->
<section class="expert-instructor top-categories py-5">
  <div class="container">
    <div class="row">
      <div class="col-lg-3"></div>
      <div class="col-lg-6">
        <h2 class="text-center mt-0 pt-0"><?php echo get_phrase('Think more clearly'); ?></h2>
        <p class="text-center mt-4 mb-4"><?php echo get_phrase('Gather your thoughts, and make your decisions clearly') ?></p>
      </div>
      <div class="col-lg-3"></div>
    </div>
    <ul class="speech-items">
        <?php $counter = 0; ?>
        <?php foreach($motivational_speechs as $key => $motivational_speech): ?>
        <?php $counter = $counter+1; ?>
        <li>
            <div class="speech-item">
                <div class="row align-items-center">
                    <div class="col-lg-4 col-md-5">
                        <div class="speech-item-img">
                            <img loading="lazy" src="<?php echo site_url('uploads/system/motivations/'.$motivational_speech['image']) ?>" alt="" />
                        </div>
                    </div>
                    <div class="col-lg-8 col-md-7">
                        <div class="speech-item-content">
                            <p class="no"><?php echo $counter; ?></p>
                            <div class="inner">
                                <h4 class="title">
                                    <?php echo $motivational_speech['title']; ?>
                                </h4>
                                <p class="info">
                                    <?php echo nl2br($motivational_speech['description']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
  </div>
</section>
<!---------  Motivetional Speech end ---------------->
<?php endif; ?>
<?php endif; ?>

<?php if(get_frontend_settings('faq_section') == 1): ?>
<?php $website_faqs = json_decode(get_frontend_settings('website_faqs'), true); ?>
<?php if(count($website_faqs) > 0): ?>
<!---------- Questions Section Start  -------------->
<section class="faq py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-2"></div>
            <div class="col-lg-8">
                <h2 class="text-center mt-0 pt-0"><?php echo get_phrase('Frequently Asked Questions') ?></h2>
                <p class="text-center mt-4 mb-5"><?php echo get_phrase('Have something to know?') ?> <?php echo get_phrase('Check here if you have any questions about us.') ?></p>
            </div>
            <div class="col-lg-2"></div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="faq-accrodion mb-0">
                    <div class="accordion" id="accordionFaq">
                        <?php foreach($website_faqs as $key => $faq): ?>
                            <?php if($key > 4) break; ?>
                            <div class="accordion-item">
                              <h2 class="accordion-header" id="<?php echo 'faqItemHeading'.$key; ?>">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo 'faqItempanel'.$key; ?>" aria-expanded="true" aria-controls="<?php echo 'faqItempanel'.$key; ?>">
                                    <?php echo $faq['question']; ?>
                                </button>
                              </h2>
                              <div id="<?php echo 'faqItempanel'.$key; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo 'faqItemHeading'.$key; ?>"  data-bs-parent="#accordionFaq">
                                <div class="accordion-body">
                                    <p><?php echo nl2br($faq['answer']); ?></p>
                                </div>
                              </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if(count($website_faqs) > 5): ?>
                        <a href="<?php echo site_url('home/faq') ?>" class="btn btn-primary mt-5"><?php echo get_phrase('See More'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!---------- Questions Section End  -------------->
<?php endif; ?>
<?php endif; ?>


<!------------- Blog Section Start ------------>
<?php $this->load->view('frontend/default-new/partials/latest_blogs'); ?>
<!------------- Blog Section End ------------>


<?php if(get_frontend_settings('promotional_section') == 1): ?>
<!------------- Become Students Section start --------->
<section class="student py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-6  <?php if (get_settings('allow_instructor') != 1) echo 'w-100'; ?>">
                <div class="student-body-1">
                    <div class="row">
                        <div class="col-lg-8 col-md-8 col-sm-8 col-8">
                            <div class="student-body-text">
                                <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/2.png')?>" alt="Student testimonial rating">
                                <h2><?php echo site_phrase('join_now_to_start_learning'); ?></h2>
                                <p><?php echo site_phrase('Learn from our quality instructors!')?> </p>
                                <a href="<?php echo site_url('sign_up'); ?>"><?php echo site_phrase('get_started'); ?></a>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-4 col-4">
                            <img loading="lazy" class="man" src="<?php echo base_url('assets/frontend/default-new/image/student-1.png')?>" alt="Lvalues student testimonial">
                        </div>
                     </div>
                </div>      
            </div>
            <?php if (get_settings('allow_instructor') == 1) : ?>
                <div class="col-lg-6 ">
                    <div class="student-body-2">
                    <div class="row">
                            <div class="col-lg-8 col-md-8 col-sm-8 col-8 ">
                                <div class="student-body-text">
                                  <img loading="lazy" src="<?php echo base_url('assets/frontend/default-new/image/2.png')?>" alt="Student testimonial rating">
                                    <h2><?php echo site_phrase('become_a_new_instructor'); ?></h2>
                                    <p><?php echo site_phrase('Teach_thousands_of_students_and_earn_money!')?> </p>
                                    <?php if($this->session->userdata('user_id')): ?>
                                       <a  href="<?php echo site_url('user/become_an_instructor'); ?>"><?php echo site_phrase('join_now'); ?></a>
                                      <?php else: ?>
                                        <a  href="<?php echo site_url('sign_up?instructor=yes'); ?>"><?php echo site_phrase('join_now'); ?></a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-4 col-sm-4 col-4">
                            <img loading="lazy" class="man" src="<?php echo base_url('assets/frontend/default-new/image/student-2.png')?>" alt="Lvalues student testimonial">
                            </div>
                        </div>  
                    </div> 
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<!------------- Become Students Section End --------->

<div class="py-4 w-100"></div>
