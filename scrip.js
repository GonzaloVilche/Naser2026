// ================================
// AÑO DEL FOOTER
// ================================

document.getElementById("year").textContent =
    new Date().getFullYear();


// ================================
// MENÚ MOBILE
// ================================

const menuButton =
    document.querySelector(".menu-btn");

const nav =
    document.querySelector("nav");

menuButton.addEventListener("click", () => {

    nav.classList.toggle("active");

});


// Cerrar menú cuando se selecciona
// una sección

document.querySelectorAll("nav a").forEach(link => {

    link.addEventListener("click", () => {

        nav.classList.remove("active");

    });

});


// ================================
// FORMULARIO
// ================================

const form =
    document.getElementById("contactForm");

const message =
    document.getElementById("form-message");


form.addEventListener("submit", function(event) {

    event.preventDefault();


    message.textContent =
        "Consulta enviada correctamente. Nos pondremos en contacto con vos.";


    form.reset();

});