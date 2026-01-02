<?php include 'includes/header.php'; ?>

<!-- Header -->
<header class="py-5 text-white text-center" style="background-color: #0c2340;">
    <div class="container">
        <h1 class="display-4 font-weight-bold" data-aos="fade-down">School Gallery</h1>
        <p class="lead" data-aos="fade-up" data-aos-delay="100">A glimpse into life at Topaz International School Minna</p>
    </div>
</header>

<!-- Gallery Section -->
<section class="py-5">
    <div class="container">
        <!-- Filter Buttons (Optional for future) -->
        <div class="row mb-4" data-aos="fade-up">
            <div class="col-12 text-center">
                <button class="btn btn-outline-primary active m-1">All</button>
                <button class="btn btn-outline-primary m-1">Campus</button>
                <button class="btn btn-outline-primary m-1">Students</button>
                <button class="btn btn-outline-primary m-1">Events</button>
                <button class="btn btn-outline-primary m-1">Sports</button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Gallery Item 1 -->
            <div class="col-md-4 col-sm-6" data-aos="zoom-in">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="card-img-top hover-scale" alt="School Building" style="height: 250px; object-fit: cover;">
                </div>
            </div>

            <!-- Gallery Item 2 -->
            <div class="col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1509062522246-3755977927d7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="card-img-top hover-scale" alt="Classroom Learning" style="height: 250px; object-fit: cover;">
                </div>
            </div>

            <!-- Gallery Item 3 -->
            <div class="col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1577896337318-2837d1d0703a?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="card-img-top hover-scale" alt="Science Lab" style="height: 250px; object-fit: cover;">
                </div>
            </div>

            <!-- Gallery Item 4 -->
            <div class="col-md-4 col-sm-6" data-aos="zoom-in">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1546410531-bb4caa6b424d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="card-img-top hover-scale" alt="Library" style="height: 250px; object-fit: cover;">
                </div>
            </div>

            <!-- Gallery Item 5 -->
            <div class="col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="100">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1564981797816-1043664bf78d?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="card-img-top hover-scale" alt="Sports Day" style="height: 250px; object-fit: cover;">
                </div>
            </div>

            <!-- Gallery Item 6 -->
            <div class="col-md-4 col-sm-6" data-aos="zoom-in" data-aos-delay="200">
                <div class="card h-100 border-0 shadow-sm overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1427504494785-3a9ca28497b1?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" class="card-img-top hover-scale" alt="Computer Lab" style="height: 250px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lightbox Modal -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body text-center p-0">
        <img src="" id="modalImage" class="img-fluid rounded shadow-lg">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var galleryImages = document.querySelectorAll('.card-img-top');
    var modalImage = document.getElementById('modalImage');
    var galleryModal = new bootstrap.Modal(document.getElementById('galleryModal'));

    galleryImages.forEach(function(img) {
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            modalImage.src = this.src;
            galleryModal.show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
