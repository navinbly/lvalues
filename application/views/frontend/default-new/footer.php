<!--------- footer Section Start--------------->
<section class="footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-12 col-sm-12 col-12 mb-5">
                <img loading="lazy" src="<?php echo base_url('uploads/system/'.get_frontend_settings('light_logo')); ?>" alt="Lvalues">
                <p><?php echo get_settings('website_description'); ?></p>
                <p class="mb-1"><strong>Learning modes:</strong> Online, offline, and hybrid.</p>
                <p class="mb-1"><strong>Support:</strong> <?php echo get_settings('system_email'); ?></p>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-4">
                <h2 class="h5"><?php echo site_phrase('Learners'); ?></h2>
                <ul>
                    <li><a href="<?php echo site_url('home/courses'); ?>"><?php echo site_phrase('all_courses'); ?></a></li>
                    <li><a href="<?php echo site_url('books'); ?>">Books</a></li>
                    <li><a href="<?php echo site_url('mock-tests'); ?>">Mock Tests</a></li>
                    <li><a href="<?php echo site_url('home/search?search_for=tutor'); ?>">Find a Tutor</a></li>
                    <li><a href="<?php echo site_url('parents'); ?>">For Parents</a></li>
                    <li><a href="<?php echo site_url('home/courses?query=school'); ?>">School Courses</a></li>
                    <li><a href="<?php echo site_url('home/courses?query=IT%20Training'); ?>">IT Training</a></li>
                    <li><a href="<?php echo site_url('sign_up'); ?>"><?php echo site_phrase('sign_up'); ?></a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-4">
                <h2 class="h5">Tutors</h2>
                <ul>
                    <?php if (get_settings('allow_instructor') == 1) : ?>
                        <li><a href="<?php echo site_url('sign_up?instructor=yes'); ?>">Become a Tutor</a></li>
                        <li><a href="<?php echo site_url('home/become_an_instructor'); ?>">Tutor Application</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo site_url('tutor-earnings'); ?>">Tutor Earnings</a></li>
                    <li><a href="<?php echo site_url('tutor-verification'); ?>">Tutor Verification</a></li>
                    <li><a href="<?php echo site_url('login'); ?>">Tutor Login</a></li>
                    <li><a href="<?php echo site_url('#lvaluesHowItWorks'); ?>">How Lvalues Works</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-4">
                <h2 class="h5">Business</h2>
                <ul>
                    <li><a href="<?php echo site_url('home/contact_us?type=corporate'); ?>">Corporate Training</a></li>
                    <li><a href="<?php echo site_url('home/courses?query=career'); ?>">Career Programs</a></li>
                    <li><a href="<?php echo site_url('home/contact_us?type=partnership'); ?>">Tutor Partnership</a></li>
                    <li><a href="<?php echo site_url('home/contact_us'); ?>">Request Support</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4 col-sm-6 col-6 mb-4">
                <h2 class="h5">Company</h2>
                <ul>
                    <li><a href="<?php echo site_url('home/about_us'); ?>"><?php echo site_phrase('about_us'); ?></a></li>
                    <li><a href="<?php echo site_url('blog'); ?>"><?php echo site_phrase('blog'); ?></a></li>
                    <li><a href="<?php echo site_url('home/contact_us'); ?>"><?php echo site_phrase('contact_us'); ?></a></li>
                    <li><a href="<?php echo site_url('home/faq'); ?>"><?php echo site_phrase('FAQ'); ?></a></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 col-md-8 col-sm-12 col-12 mb-4">
                <h2 class="h5">Support and Legal</h2>
                <ul class="d-flex flex-wrap gap-3 mb-0">
                    <li><a href="<?php echo site_url('home/privacy_policy'); ?>"><?php echo site_phrase('privacy_policy'); ?></a></li>
                    <li><a href="<?php echo site_url('home/terms_and_condition'); ?>"><?php echo site_phrase('terms_and_condition'); ?></a></li>
                    <li><a href="<?php echo site_url('home/refund_policy'); ?>"><?php echo site_phrase('refund_policy'); ?></a></li>
                    <li><a href="<?php echo site_url('home/contact_us'); ?>">Support SLA</a></li>
                </ul>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-12 col-12 mb-4">
                <h2 class="h5"><?php echo get_phrase('Subscribe to our Newsletter'); ?></h2>
                <form class="ajaxForm resetable" action="<?php echo site_url('home/subscribe_to_our_newsletter'); ?>" method="post">
                    <input type="email" class="form-control" id="subscribe_email" placeholder="<?php echo get_phrase('Enter your email address'); ?>" name="email">
                    <button class="form-arrow" type="submit" aria-label="Subscribe"><i class="fa-solid fa-arrow-right-long"></i></button>
                </form>
            </div>
        </div>
        <div class="lattest-news">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                    <p class="mb-0">Lvalues supports learners, parents, tutors, and teams with structured courses and tutor-led learning.</p>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                    <div class="icon right-icon">
                        <ul class="nav justify-content-end">
                          <li class="nav-item">
                            <a target="_blank" rel="noopener" href="<?php echo get_settings('footer_link'); ?>">
                              <?php echo site_phrase(get_settings('footer_text')); ?>
                            </a>
                          </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!--------- footer Section End--------------->

<!-- PAYMENT MODAL -->
<!-- Modal -->
<?php
$paypal_info = json_decode(get_settings('paypal'), true);
$stripe_info = json_decode(get_settings('stripe_keys'), true);
if ($paypal_info[0]['active'] == 0) {
  $paypal_status = 'disabled';
}else {
  $paypal_status = '';
}
if ($stripe_info[0]['active'] == 0) {
  $stripe_status = 'disabled';
}else {
  $stripe_status = '';
}
?>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/917420920360?text=Hello%20Lvalues,%20I%20need%20help%20with%20courses."
   class="lvalues-whatsapp-float"
   target="_blank"
   aria-label="Chat on WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>

<style>
.lvalues-whatsapp-float{
    position: fixed;
    width: 62px;
    height: 62px;
    bottom: 24px;
    right: 24px;
    background: #25D366;
    color: #fff !important;
    border-radius: 50%;
    text-align: center;
    font-size: 34px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.25);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s ease;
    text-decoration: none !important;
}

.lvalues-whatsapp-float:hover{
    transform: scale(1.08);
    color: #fff !important;
    text-decoration: none !important;
    box-shadow: 0 8px 24px rgba(0,0,0,0.35);
}

@media(max-width:768px){
    .lvalues-whatsapp-float{
        width: 58px;
        height: 58px;
        font-size: 32px;
        bottom: 18px;
        right: 18px;
    }
}
</style>
