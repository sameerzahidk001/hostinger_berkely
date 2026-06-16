<!-- Hidden Inputs to Store Content -->
<input type="hidden" name="hero_title" id="hidden-hero-title">
    <input type="hidden" name="hero_subtitle" id="hidden-hero-subtitle">

    <section id="header-course" class="relative overflow-hidden" style="min-height: 100vh;">
        <!-- Bootstrap 3 Panel Structure for Hero Section -->
        <div class="panel panel-default" style="position: relative; z-index: 30; overflow: hidden; min-height: 100vh;">
            <div class="panel-body" style="padding: 0;">
                <!-- Hero Image -->
                <img src="{{ asset($hero->image ?? 'frontend/images/jpg/60.jpg') }}" class="img-responsive editable-image" alt="Hero Image" style="width:100%">
            </div>
            <div class="panel-footer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 40; display: flex; justify-content: center; align-items: center; background: rgba(0, 0, 0, 0.5);">
                <div class="text-center text-white">
                    <h1 class="text-uppercase" style="font-size: 68px; line-height: 60px;" contenteditable="true" id="hero-title">
                        {{ $hero->title ?? 'Knowledge Universe' }}
                    </h1>
                    <p class="lead" contenteditable="true" id="hero-subtitle">
                        {{ $hero->subtitle ?? 'Educating people who make a difference in the world' }}
                    </p>
                </div>
            </div>
        </div>
        <!-- End of Panel -->
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
    const heroTitle = document.getElementById('hero-title');
    const heroSubtitle = document.getElementById('hero-subtitle');

    // Add event listeners for 'input' event to capture changes in contenteditable elements
    heroTitle.addEventListener('input', function () {
        document.getElementById('hidden-hero-title').value = heroTitle.innerText;  // Update hidden input with the content
    });

    heroSubtitle.addEventListener('input', function () {
        document.getElementById('hidden-hero-subtitle').value = heroSubtitle.innerText;  // Update hidden input with the content
    });
});
        </script>