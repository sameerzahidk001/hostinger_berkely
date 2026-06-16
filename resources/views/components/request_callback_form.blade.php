<section
    class="card-hidden flex flex-col gap-10  min-[1200px]:px-[72px] md:px-12 px-6 my-0 justify-center items-center relative py-10 md:min-h-[530px]  bg-navy"
    id="contact-form">
    <div class="flex flex-col gap-6 w-full  lg:flex-row">
        <div class="flex flex-col text-white gap-4 md:min-w-[400px] md:max-w-[400px] shrink-0">
            <h1 class="text-[36px] capitalize">Request a call back</h1>

            <h1 class="text-[24px] md:text-[28px] capitalize">Connect with a programme adviser for a 1:1 session</h1>
            <p class="mb-2">Our program advisors have guided senior executives worldwide in selecting the best programs to achieve their career goals. Schedule a consultation today for personalized advice on choosing the right program for you.</p>
            <p class="mb-2">To find out more about learning with BERKELEY SQUARE, or to reserve your place, contact us at <a href="mailto:info@berkeleyme.com" class="font-bold underline">info@berkeleyme.com</a> and one of our admissions/support team will be in touch.</p>
            <p class="mb-2">Call us now on +44 7306 279111 during UK Opening hours, Monday – Friday 7:30am – 9:00pm.</p>
            <p class="mb-2">If you are a current student, please <a href="mailto:info@berkeleyme.com" class="font-bold">click here</a> to get in touch with our customer service team.</p>
        </div>
        <div class="flex-1 flex flex-col gap-4">

            <form  id="callbackForm" action="{{ route('zoho.callback-form') }}"  method="POST" class="flex flex-col gap-6">
                @csrf

                <input type="hidden" name="Inquiry_Source" value="Website">
                <input type="hidden" name="Date_of_Entry" value="">

                

                <h1 class="text-[36px] text-white">Personal Details</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <label class="w-full">
                        <span class="block text-white mb-1">First Name <span class="text-red-500">*</span></span>
                        <input type="text" class="w-full min-h-10 px-4 outline-none" name="Student_First_Name" placeholder="Your First Name*">
                    </label>

                    <label class="w-full">
                        <span class="block text-white mb-1">Last Name <span class="text-red-500">*</span></span>
                        <input type="text" class="w-full min-h-10 px-4 outline-none" name="Student_Last_Name" placeholder="Your Last Name*">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white">Personal Email <span class="text-red-500">*</span> </span>
                        <input type="email" class="w-full min-h-10 px-4 outline-none" name="Parent_Email" placeholder="Your Personal Email">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white">Mobile Contact No <span class="text-red-500">*</span></span>
                        <input type="tel" id="phone2" class="w-full min-h-10 px-4 outline-none" name="Phone_No" placeholder="Your Mobile Contact No">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white">Company Name <span class="text-red-500">*</span></span>
                        <input type="text" class="w-full min-h-10 px-4 outline-none" name="Parent_Country_of_Residence" placeholder="Company Name">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white">Designation <span class="text-red-500">*</span></span>
                        <input type="text" class="w-full min-h-10 px-4 outline-none" name="Parent_Country_of_Residence" placeholder="Your Designation">
                    </label>

                    <label class="w-full">
                        <span class="block text-white mb-1">Country <span class="text-red-500">*</span></span>
                        <select name="Preferred_Start_Date" class="w-full min-h-10 px-4 outline-none">
                            <option value="Next Year">-- Select Country --</option>
                            
                        </select>
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white">Nationality <span class="text-red-500">*</span></span>
                        <input type="text" class="w-full min-h-10 px-4 outline-none" name="Parent_Country_of_Residence" placeholder="Your Nationality">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white">City <span class="text-red-500">*</span></span>
                        <input type="text" class="w-full min-h-10 px-4 outline-none" name="Parent_Country_of_Residence" placeholder="Your City">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white"> Date of Birth <span class="text-red-500">*</span></span>
                        <input type="date" class="w-full min-h-10 px-4 outline-none" name="Parent_Country_of_Residence" placeholder="Your  Date of Birth">
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white"> Gender <span class="text-red-500">*</span></span>
                        <select name="Preferred_Start_Date" class="w-full min-h-10 px-4 outline-none">
                            <option value="">-- Select Gender --</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Intersex">Intersex</option>
                        </select>
                    </label>

                    <label class="w-full">
                        <span class="block mb-1 text-white"> Linkedin Profile</span>
                        <input type="url" class="w-full min-h-10 px-4 outline-none" name="Parent_Country_of_Residence" placeholder="Your Linkedin profile link">
                    </label>

                    {{--<label class="w-full">
                        <span class="block text-white mb-1">Category</span>
                        <select name="How_To_Help" class="w-full min-h-10 px-4 outline-none">
                            <option value="Lower Secondary School">Lower Secondary School</option>
                            <option value="IGCSE">IGCSE</option>
                            <option value="Sixth Form (A-Levels)">Sixth Form (A-Levels)</option>
                            <option value="School Club">School Club</option>
                            <option value="School Camp">School Camp</option>
                            <option value="Quran Learning Center">Quran Learning</option>
                        </select>
                    </label>

                    <label class="w-full">
                        <span class="block text-white mb-1">Course</span>
                        <select name="Key_Stage" class="w-full min-h-10 px-4 outline-none">
                            <option value="Key Stage 2: Years 7-11">Key Stage 2: Years 7-11</option>
                            <option value="Key Stage 3: Years 11-14">Key Stage 3: Years 11-14</option>
                            <option value="Key Stage 4: Years 14-16">Key Stage 4: Years 14-16</option>
                            <option value="Key Stage 5: Years 16-18">Key Stage 5: Years 16-18</option>
                            <option value="">Others</option>
                        </select>
                    </label>--}}
                </div>

               
                <label class="w-full">
                    <span class="block mb-1 text-white">Have any inquiry? Write to us below</span>
                    <textarea name="Query" class="w-full outline-none p-4 min-h-[100px]" placeholder="Please let us know if you have any questions or how our admissions team can help you."></textarea>
                </label> 

                <button type="submit" id="submitButton2" class="ml-auto border text-white py-3 min-w-[150px] max-w-[150px] mt-0 px-3 bg-primary_orange hover:text-dark text-[20px] leading-[20px] font-semibold transition-all delay-100 duration-100">
                    <span id="buttonText">Submit</span>
                    <span id="spinner" class="hidden ml-2 spinner-border" role="status" aria-hidden="true"></span>
                </button>

                <div id="responseMessage" class="flex items-center justify-center text-center text-white font-bold">
                </div>
                
            </form>

        </div>
        
    </div>

</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to get current date and time in the required format
        function getCurrentDateTime() {
            const now = new Date();
            // Format the date to yyyy-MM-dd'T'HH:mm:ss
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
        }

        // Set the value of the hidden input field
        const dateInput = document.querySelector('input[name="Date_of_Entry"]');
        if (dateInput) {
            dateInput.value = getCurrentDateTime();
        }
    });
</script>
