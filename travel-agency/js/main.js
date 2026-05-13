'use strict';

const TravelApp = {

    init() {
        this.setupCanvas();
        this.setupCounters();
        this.setupContactForm();
    },

    // Rysuje animowane kropki w tle sekcji hero
    setupCanvas() {
        var plansza = document.getElementById('heroCanvas');
        if (!plansza) return;

        var ctx = plansza.getContext('2d');

        // Dopasuj rozmiar canvasa do okna
        function dopasujRozmiar() {
            plansza.width  = plansza.offsetWidth;
            plansza.height = plansza.offsetHeight;
        }
        dopasujRozmiar();
        window.addEventListener('resize', dopasujRozmiar);

        // Tablica 80 kropek z losową pozycją, prędkością i przezroczystością
        var kropki = [];
        for (var i = 0; i < 80; i++) {
            kropki.push({
                x:           Math.random() * plansza.width,
                y:           Math.random() * plansza.height,
                promien:     Math.random() * 2 + 0.5,
                predkoscX:   (Math.random() - 0.5) * 0.5,
                predkoscY:   (Math.random() - 0.5) * 0.5,
                przezroczystosc: Math.random() * 0.6 + 0.2
            });
        }

        // Pętla animacji – odświeża klatkę i przesuwa każdą kropkę
        function rysuj() {
            ctx.clearRect(0, 0, plansza.width, plansza.height);

            // Ciemny gradient tła
            var tlo = ctx.createLinearGradient(0, 0, plansza.width, plansza.height);
            tlo.addColorStop(0, '#1a2236');
            tlo.addColorStop(1, '#0f1623');
            ctx.fillStyle = tlo;
            ctx.fillRect(0, 0, plansza.width, plansza.height);

            // Rysuj każdą kropkę i przesuń ją
            for (var i = 0; i < kropki.length; i++) {
                var k = kropki[i];

                ctx.beginPath();
                ctx.arc(k.x, k.y, k.promien, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(74, 144, 226, ' + k.przezroczystosc + ')';
                ctx.fill();

                k.x += k.predkoscX;
                k.y += k.predkoscY;

                // Odbij od krawędzi canvasa
                if (k.x < 0 || k.x > plansza.width)  k.predkoscX *= -1;
                if (k.y < 0 || k.y > plansza.height) k.predkoscY *= -1;
            }

            requestAnimationFrame(rysuj);
        }

        rysuj();
    },

    // Animuje liczniki statystyk od 0 do docelowej wartości
    setupCounters() {
        var liczniki = document.querySelectorAll('.stat-number');
        if (liczniki.length === 0) return;

        // Uruchom animację dopiero gdy licznik pojawi się na ekranie
        var obserwator = new IntersectionObserver(function(lista) {
            lista.forEach(function(pozycja) {
                if (pozycja.isIntersecting) {
                    var element  = pozycja.target;
                    var docelowa = parseInt(element.getAttribute('data-target'));
                    var aktualna = 0;
                    var krok     = Math.ceil(docelowa / 60);

                    var timer = setInterval(function() {
                        aktualna += krok;
                        if (aktualna >= docelowa) {
                            aktualna = docelowa;
                            clearInterval(timer);
                        }
                        element.textContent = aktualna.toLocaleString('pl-PL');
                    }, 30);

                    obserwator.unobserve(element);
                }
            });
        });

        liczniki.forEach(function(element) {
            obserwator.observe(element);
        });
    },

    // Waliduje formularz kontaktowy przed wysłaniem
    setupContactForm() {
        var formularz = document.getElementById('contactForm');
        if (!formularz) return;

        formularz.addEventListener('submit', function(e) {
            e.preventDefault();

            var pola   = ['name', 'email', 'subject', 'message'];
            var poprawny = true;

            // Podświetl czerwoną ramką pola które są puste
            pola.forEach(function(id) {
                var pole = document.getElementById(id);
                if (!pole) return;
                if (!pole.value.trim()) {
                    pole.style.borderColor = '#e07070';
                    poprawny = false;
                } else {
                    pole.style.borderColor = '';
                }
            });

            // Sprawdź czy email ma prawidłowy format
            var poleEmail = document.getElementById('email');
            if (poleEmail && poleEmail.value && !poleEmail.checkValidity()) {
                poleEmail.style.borderColor = '#e07070';
                poprawny = false;
            }

            // Jeśli wszystko OK – wyczyść formularz i pokaż komunikat
            if (poprawny) {
                formularz.reset();
                var komunikat = document.getElementById('formSuccess');
                if (komunikat) {
                    komunikat.style.display = 'block';
                    setTimeout(function() { komunikat.style.display = 'none'; }, 5000);
                }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    TravelApp.init();
});
