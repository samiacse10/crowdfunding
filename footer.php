<?php
require_once 'config.php';
include 'db.php';

// Quick Links array (can make dynamic from DB if needed)
$quickLinks = [
    ['label'=>'Home','url'=>'index.php'],
    ['label'=>'About Us','url'=>'footer_links/about_us.php'],
    ['label'=>'How It Works','url'=>'#'],
    ['label'=>'FAQs','url'=>'#'],
    ['label'=>'Terms & Conditions','url'=>'#'],
    ['label'=>'Privacy Policy','url'=>'#'],
];

// Fetch social links from DB (fallback to array if table missing)
$socialLinks = [];
$socialQuery = @mysqli_query($conn,"SELECT * FROM social_links");
if($socialQuery){
    while($row = mysqli_fetch_assoc($socialQuery)){
        $socialLinks[] = ['platform'=>$row['platform'],'url'=>$row['url']];
    }
} else {
    // fallback if DB table missing
    $socialLinks = [
        ['platform'=>'facebook','url'=>'https://facebook.com/yourpage'],
        ['platform'=>'twitter','url'=>'https://twitter.com/yourpage'],
        ['platform'=>'instagram','url'=>'https://instagram.com/yourpage'],
        ['platform'=>'youtube','url'=>'https://youtube.com/yourpage'],
        ['platform'=>'linkedin','url'=>'https://linkedin.com/yourpage'],
    ];
}
?>

<footer class="site-footer">
    <!-- Full width top section -->
    <div class="footer-top w-100 py-5 px-4 px-lg-5">
        <div class="row gy-4 m-0 w-100">

            <!-- About -->
            <div class="col-lg-3 col-md-6">
                <h5><i class="fa-solid fa-seedling me-2"></i>About Crowdfund</h5>
                <p>We are a leading crowdfunding platform helping individuals and organizations bring their innovative ideas to life. Join us today and make a difference!</p>
                <a href="footer_links/about_us.php" class="btn btn-sm btn-outline-light mt-2">Learn More</a>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-3 col-md-6">
                <h5><i class="fa-solid fa-link me-2"></i>Quick Links</h5>
                <ul class="list-unstyled">
                    <?php foreach($quickLinks as $link): ?>
                        <li><a href="<?php echo $link['url']; ?>"><i class="fas fa-chevron-right me-2"></i><?php echo $link['label']; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Contact Info + Map -->
            <div class="col-lg-3 col-md-6">
                <h5><i class="fa-solid fa-location-dot me-2"></i>Contact Info</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i>University of Barishal,Barishal,Bangladesh</li>
                    <li><i class="fas fa-phone me-2"></i>01533114856</li>
                    <li><i class="fas fa-envelope me-2"></i>support@crowdfund.com</li>
                    <li><i class="fas fa-clock me-2"></i>Mon - Fri: 9:00 AM - 6:00 PM</li>
                </ul>
                <!-- Google Map Embed -->
                <div class="mt-2 map-container">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3023.892765793351!2d-74.00601528459485!3d40.71277577933066!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDDCsDQyJzQ2LjAiTiA3NMKwMDAnMDkuMCJX!5e0!3m2!1sen!2sbd!4v1677111111111!5m2!1sen!2sbd"
                        width="100%" height="180" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>

            <!-- Newsletter & Social -->
            <div class="col-lg-3 col-md-6">
                <h5><i class="fa-solid fa-envelope me-2"></i>Newsletter</h5>
                <p>Subscribe to get updates on new campaigns and offers.</p>
                <form class="d-flex mb-3 newsletter-form" action="subscribe.php" method="POST">
                    <input type="email" name="email" class="form-control me-2" placeholder="Your Email" required>
                    <button class="btn btn-light" type="submit"><i class="fas fa-paper-plane"></i></button>
                </form>

                <h5 class="mt-4"><i class="fa-solid fa-heart me-2"></i>Follow Us</h5>
                <div class="d-flex gap-2 social-icons">
                    <?php foreach($socialLinks as $social):
                        $icon = '';
                        switch(strtolower($social['platform'])){
                            case 'facebook': $icon='fab fa-facebook-f'; break;
                            case 'twitter': $icon='fab fa-twitter'; break;
                            case 'instagram': $icon='fab fa-instagram'; break;
                            case 'youtube': $icon='fab fa-youtube'; break;
                            case 'linkedin': $icon='fab fa-linkedin-in'; break;
                        }
                    ?>
                        <a href="<?php echo $social['url']; ?>" target="_blank" class="btn btn-outline-light btn-sm social-btn">
                            <i class="<?php echo $icon; ?>"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Full width footer bottom -->
    <div class="footer-bottom w-100 py-3 border-top text-center text-md-start px-4 px-lg-5" style="border-color: rgba(167, 224, 181, 0.3);">
        <div class="row m-0 w-100">
            <div class="col-12 d-flex flex-column flex-md-row justify-content-between align-items-center">
                <p class="mb-0"><i class="fa-regular fa-copyright me-1"></i><?php echo date('Y'); ?> Crowdfunding Platform. All Rights Reserved.</p>
                <ul class="list-inline mb-0">
                    <li class="list-inline-item"><a href="#">Privacy</a></li>
                    <li class="list-inline-item"><span class="mx-2" style="color: rgba(167, 224, 181, 0.5);">|</span></li>
                    <li class="list-inline-item"><a href="#">Terms</a></li>
                    <li class="list-inline-item"><span class="mx-2" style="color: rgba(167, 224, 181, 0.5);">|</span></li>
                    <li class="list-inline-item"><a href="#">Sitemap</a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap & Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
.site-footer {
    background: linear-gradient(135deg, #1a472a 0%, #2d6a4f 50%, #40916c 100%);
    color: white;
    width: 100vw;
    position: relative;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    box-sizing: border-box;
    border-top: 3px solid #74c69d;
}

.footer-top, .footer-bottom {
    background: inherit;
}

.site-footer h5 {
    border-bottom: 2px solid #a7e0b5;
    display: inline-block;
    padding-bottom: 8px;
    margin-bottom: 20px;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
}

.site-footer h5 i {
    color: #a7e0b5;
    margin-right: 8px;
}

.site-footer a {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

.site-footer a:hover {
    color: #a7e0b5;
    transform: translateX(3px);
}

.site-footer ul li {
    margin-bottom: 8px;
}

.site-footer ul li i {
    color: #a7e0b5;
    width: 20px;
}

.site-footer .btn-outline-light {
    border: 2px solid #a7e0b5;
    color: white;
    background: rgba(167, 224, 181, 0.1);
    border-radius: 25px;
    padding: 0.375rem 1.5rem;
    transition: all 0.3s ease;
}

.site-footer .btn-outline-light:hover {
    background: #a7e0b5;
    color: #1a472a;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(167, 224, 181, 0.4);
}

/* Newsletter form */
.newsletter-form .form-control {
    background: rgba(255,255,255,0.1);
    border: 2px solid rgba(167, 224, 181, 0.3);
    color: white;
    border-radius: 25px;
    padding: 0.5rem 1rem;
}

.newsletter-form .form-control:focus {
    background: rgba(255,255,255,0.15);
    border-color: #a7e0b5;
    box-shadow: 0 0 0 3px rgba(167, 224, 181, 0.2);
    color: white;
}

.newsletter-form .form-control::placeholder {
    color: rgba(255,255,255,0.6);
}

.newsletter-form .btn-light {
    background: #a7e0b5;
    border: 2px solid #a7e0b5;
    color: #1a472a;
    border-radius: 25px;
    padding: 0.5rem 1.2rem;
    transition: all 0.3s ease;
}

.newsletter-form .btn-light:hover {
    background: transparent;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(167, 224, 181, 0.4);
}

/* Social icons */
.social-icons {
    margin-top: 10px;
}

.social-icons .social-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 2px solid #a7e0b5;
    background: rgba(167, 224, 181, 0.1);
    transition: all 0.3s ease;
}

.social-icons .social-btn i {
    font-size: 1rem;
}

.social-icons .social-btn:hover {
    background: #a7e0b5;
    color: #1a472a;
    transform: translateY(-3px) rotate(360deg);
}

/* Map container */
.map-container {
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid rgba(167, 224, 181, 0.3);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.map-container iframe {
    display: block;
}

/* Footer bottom */
.footer-bottom {
    border-top: 2px solid rgba(167, 224, 181, 0.3) !important;
    background: rgba(26, 71, 42, 0.5);
    backdrop-filter: blur(5px);
}

.footer-bottom a {
    color: rgba(255,255,255,0.8);
    font-size: 0.95rem;
    padding: 0 5px;
}

.footer-bottom a:hover {
    color: #a7e0b5;
    transform: translateY(-2px);
}

.footer-bottom .list-inline-item span {
    color: rgba(167, 224, 181, 0.3);
}

/* Dark mode adjustments */
body.dark-mode .site-footer {
    background: linear-gradient(135deg, #0f2a1a 0%, #1e4a33 50%, #2d6a4f 100%);
    border-top: 3px solid #52b788;
}

body.dark-mode .footer-bottom {
    background: rgba(15, 42, 26, 0.8);
}

body.dark-mode .newsletter-form .form-control {
    background: rgba(0,0,0,0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .site-footer h5 {
        font-size: 1.2rem;
    }
    
    .footer-top {
        padding: 2rem 1rem !important;
    }
    
    .social-icons {
        justify-content: center;
    }
    
    .newsletter-form {
        flex-direction: column;
        gap: 10px;
    }
    
    .newsletter-form .form-control {
        margin-right: 0 !important;
    }
    
    .footer-bottom .list-inline {
        margin-top: 10px;
    }
    
    .footer-bottom .list-inline-item span {
        display: none;
    }
    
    .footer-bottom .list-inline-item:after {
        content: "|";
        margin: 0 8px;
        color: rgba(167, 224, 181, 0.3);
    }
    
    .footer-bottom .list-inline-item:last-child:after {
        content: "";
    }
}

/* Animation for social icons */
@keyframes float {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-3px); }
    100% { transform: translateY(0px); }
}

.social-icons .social-btn {
    animation: float 3s ease-in-out infinite;
}

.social-icons .social-btn:nth-child(2) { animation-delay: 0.2s; }
.social-icons .social-btn:nth-child(3) { animation-delay: 0.4s; }
.social-icons .social-btn:nth-child(4) { animation-delay: 0.6s; }
.social-icons .social-btn:nth-child(5) { animation-delay: 0.8s; }

/* Ensure rows take full width */
.site-footer .row {
    margin-left: 0;
    margin-right: 0;
    max-width: 100%;
}

/* Remove any Bootstrap container constraints */
.container, .container-fluid {
    padding-left: 0;
    padding-right: 0;
    margin-left: 0;
    margin-right: 0;
    max-width: 100%;
}
</style>