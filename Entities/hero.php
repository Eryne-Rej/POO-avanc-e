<section class="w-full flex justify-center my-25">

    <div class="absolute top-45 sm:top-60 text-white text-center">
        <h3 class=" text-lg md:text-xl lg:text-4xl">Le Seigneur des anneaux</h3>
        <h1 class="text-xl md:text-2xl lg:text-5xl font-bold">Matamata, Nouvelle-Zélande</h1>

        <form action="" class="hidden sm:flex flex-row text-black bg-[#D9D9D9]/50 rounded-l-[40px] font-bold rounded-r-[50px] md:pl-4  text-white gap-5 relative my-10">
            <select name="depart" id="depart"  class="hidden lg:block">
                <option value="lyon">Lyon</option>
                <option value="paris">Paris</option>
            </select>
            <hr>
            <select name="arrivee" id="arrivee" class="hidden lg:block">
                <option value="nouvelle-zelande">Nouvelle-Zélande</option>
            </select>
            <input type="date" name="departDate" id="departDate">
            <input type="date" name="arriveeDate" id="arriveeDate">
            <input type="number" name="persons" id="persons" class="w-20">
            <label for="persons"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><!-- Uploaded to: SVG Repo, www.svgrepo.com, Generator: SVG Repo Mixer Tools --> <path d="M13 20V18C13 15.2386 10.7614 13 8 13C5.23858 13 3 15.2386 3 18V20H13ZM13 20H21V19C21 16.0545 18.7614 14 16 14C14.5867 14 13.3103 14.6255 12.4009 15.6311M11 7C11 8.65685 9.65685 10 8 10C6.34315 10 5 8.65685 5 7C5 5.34315 6.34315 4 8 4C9.65685 4 11 5.34315 11 7ZM18 9C18 10.1046 17.1046 11 16 11C14.8954 11 14 10.1046 14 9C14 7.89543 14.8954 7 16 7C17.1046 7 18 7.89543 18 9Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg></label>
            <button type="submit" class="bg-[#E684BE] cursor-pointer py-2 px-10 m-1 rounded-[50px] text-lg font-bold">Rechercher</button>
        </form>
    </div>

        <div class="bg-black rounded-[20px] overflow-hidden">
            <img src="<?= $appBase ?>public/img/hero_anneaux.png" alt="Destination de tournage du Seigneur des anneaux" class="w-full h-auto">
        </div>
</section>