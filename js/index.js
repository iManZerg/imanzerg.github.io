const form = document.getElementById("calcForm");
const nameInput = document.getElementById("name");
const dateInput = document.getElementById("birthdate");
const nameError = document.getElementById("nameError");
const dateError = document.getElementById("dateError");
const submitBtn = document.getElementById("submitBtn");
const resultsSection = document.getElementById("result");
const h2_result = document.getElementById("main-wrapper_h2");

const resultsContent = document.getElementById("resultsContent");
const healthCardResults = document.getElementById("healthCardResults");


const circle1 = document.getElementById("circle1");
const circle2 = document.getElementById("circle2");
const circle3 = document.getElementById("circle3");
const circle4 = document.getElementById("circle4");
const circle5 = document.getElementById("circle5");
const circle6 = document.getElementById("circle6");
const circle7 = document.getElementById("circle7");
const circle8 = document.getElementById("circle8");
const circle9 = document.getElementById("circle9");
const circle10 = document.getElementById("circle10");
const circle11 = document.getElementById("circle11");
const circle12 = document.getElementById("circle12");
const diamondSoul = document.getElementById("diamondSoul");
const brandCodeCircle = document.getElementById("brandCodeCircle");






let results;

// function validateName() {
//   const value = nameInput.value.trim();
//   const regex = /^[A-Za-zА-Яа-яІіЇїЄєҐґ]+(?:\s+[A-Za-zА-Яа-яІіЇїЄєҐґ]+)*$/u;

//   if (!value) {
//     nameError.textContent = "Поле обов’язкове";
//     return false;
//   }
//   if (!regex.test(value)) {
//     nameError.textContent = "Мін. 2 символи, тільки літери";
//     return false;
//   }
//   nameError.textContent = "";
//   return true;
// }

function validateName() {
  const value = nameInput.value.trim();
  const regex = /^[A-Za-zА-Яа-яІіЇїЄєҐґ]+(?:\s+[A-Za-zА-Яа-яІіЇїЄєҐґ]+)*$/u;

  // Якщо поле порожнє — це тепер нормально, повертаємо true
  if (!value) {
    nameError.textContent = "";
    return true; 
  }

  // Якщо ж щось введено, перевіряємо на відповідність буквам
  if (!regex.test(value)) {
    nameError.textContent = "Мін. 2 символи, тільки літери";
    return false;
  }

  nameError.textContent = "";
  return true;
}

function validateDate() {
  const value = dateInput.value;
  if (!value) {
    dateError.textContent = "Поле обов’язкове";
    return false;
  }

  const parts = value.split(".");
  if (parts.length !== 3) {
    dateError.textContent = "Некоректна дата";
    return false;
  }

  const [day, month, year] = parts;
  const isoDate = `${year}-${month}-${day}`;
  const date = new Date(isoDate);
  const today = new Date();
  const minDate = new Date("1900-01-01");
  const maxDate = new Date("2025-12-31");

  if (isNaN(date.getTime())) {
    dateError.textContent = "Некоректна дата";
    return false;
  }
  if (date > today) {
    dateError.textContent = "Дата не може бути в майбутньому";
    return false;
  }
  if (date < minDate || date > maxDate) {
    dateError.textContent = "Дата має бути 1900–2025";
    return false;
  }

  dateError.textContent = "";
  return true;
}


function checkFormValidity() {
  const isNameValid = validateName();
  const isDateValid = validateDate();
  submitBtn.disabled = !(isNameValid && isDateValid);
}




nameInput.addEventListener("input", checkFormValidity);
dateInput.addEventListener("input", checkFormValidity);
// Маска для дати дд.мм.рррр
dateInput.addEventListener("input", (e) => {
  let v = e.target.value.replace(/\D/g, "");

  if (v.length > 8) v = v.slice(0, 8);

  // Формуємо дд.мм.рррр
  if (v.length >= 5) {
    e.target.value = `${v.slice(0, 2)}.${v.slice(2, 4)}.${v.slice(4, 8)}`;
  } else if (v.length >= 3) {
    e.target.value = `${v.slice(0, 2)}.${v.slice(2, 4)}`;
  } else {
    e.target.value = v;
  }
});

// Переписуємо validateDate — приймаємо дд.мм.рррр
function validateDate() {
  const value = dateInput.value;
  if (!value) {
    dateError.textContent = "Поле обов’язкове";
    return false;
  }

  // Переводимо дд.мм.рррр → рррр-мм-дд
  const parts = value.split(".");
  if (parts.length !== 3) {
    dateError.textContent = "Некоректна дата";
    return false;
  }

  const [day, month, year] = parts;
  const isoDate = `${year}-${month}-${day}`;
  const date = new Date(isoDate);
  const today = new Date();
  const minDate = new Date("1900-01-01");
  const maxDate = new Date("2025-12-31");

  if (isNaN(date.getTime())) {
    dateError.textContent = "Некоректна дата";
    return false;
  }

  if (date > today) {
    dateError.textContent = "Дата не може бути в майбутньому";
    return false;
  }
  if (date < minDate || date > maxDate) {
    dateError.textContent = "Дата має бути 1900–2025";
    return false;
  }

  dateError.textContent = "";
  return true;
}


function processNumber(num) {
  // 1️⃣ Якщо число ≤ 22 — повертаємо без змін
  if (num <= 22) {
    return num;
  }

  // 2️⃣ Якщо двозначне і більше 22 — зменшуємо до 22
  if (num > 22 && num < 100) {
    return reduceTo22(num);
  }

  // 3️⃣ Якщо трицифрове (100–999)
  if (num >= 100 && num <= 999) {
    const hundredsPart = Math.floor(num / 10);
    const lastDigit = num % 10;
    const combined = hundredsPart + lastDigit;
    return reduceTo22(combined);
  }

  // 4️⃣ Якщо поза діапазоном — просто повертаємо як є
  return num;
}


form.addEventListener("submit", function (e) {
  e.preventDefault();

  if (!validateName() || !validateDate()) return;
  // Переводимо дд.мм.рррр → yyyy-mm-dd
  const [day, month, year] = dateInput.value.split(".");
  const isoDate = `${year}-${month}-${day}`;

  results = calculateResults(isoDate);
  console.log(results);
  resultsContent.innerHTML = `
    <div id="A" class="level-1">${results.A}</div>
    <div id="B" class="level-1">${results.B}</div>
    <div id="C" class="level-1">${results.C}</div>
    <div id="D" class="level-1">${results.D}</div>
    <div id="I" class="level-1">${results.I}</div>


    <!-- Другий рівень -->
    <div id="E" class="level-2">${results.E}</div>
    <div id="F" class="level-2">${results.F}</div>
    <div id="G" class="level-2">${results.G}</div>
    <div id="H" class="level-2">${results.H}</div>

    <!-- Третій рівень -->
    <div id="I1" class="level-3">${results.I1}</div>
    <div id="N" class="level-3">${results.N}</div>
    <div id="J" class="level-3">${results.J}</div>
    <div id="O" class="level-3">${results.O}</div>
    <div id="K" class="level-3">${results.K}</div>
    <div id="P" class="level-3">${results.P}</div>
    <div id="L" class="level-3">${results.L}</div>
    <div id="Q" class="level-3">${results.Q}</div>
    <div id="M" class="level-3">${results.M}</div>
    <div id="I2" class="level-3">${results.I2}</div>

    <!-- Четвертий рівень -->
    <div id="J1" class="level-4">${results.J1}</div>
    <div id="N1" class="level-4">${results.N1}</div>
    <div id="K1" class="level-4">${results.K1}</div>
    <div id="O1" class="level-4">${results.O1}</div>
    <div id="L1" class="level-4">${results.L1}</div>
    <div id="P1" class="level-4">${results.P1}</div>
    <div id="M1" class="level-4">${results.M1}</div>
    <div id="Q1" class="level-4">${results.Q1}</div>
    <div id="R" class="level-4">${results.R}</div>
    <div id="S" class="level-4">${results.S}</div>
    <div id="T" class="level-4">${results.T}</div>

    <div id="T1" class="level-4">${results.T1}</div>
    <div id="T2" class="level-4">${results.T2}</div>
    <div id="T3" class="level-4">${results.T3}</div>
    <div id="T4" class="level-4">${results.T4}</div>
`
    ;

  healthCardResults.innerHTML = `

    <div id="sahasrara1" class="">${results.A}</div>
    <div id="sahasrara2" class="">${results.B}</div>
    <div id="sahasrara3" class="">${reduceTo22(results.A + results.B)}</div>

    <div id="ajna1" class="">${results.J1}</div>
    <div id="ajna2" class="">${results.L1}</div>
    <div id="ajna3" class="">${reduceTo22(results.J1 + results.L1)}</div>

    <div id="vishuddha1" class="">${results.J}</div>
    <div id="vishuddha2" class="">${results.L}</div>
    <div id="vishuddha3" class="">${reduceTo22(results.J + results.L)}</div>

    <div id="anahata1" class="">${results.R}</div>
    <div id="anahata2" class="">${results.S}</div>
    <div id="anahata3" class="">${reduceTo22(results.R + results.S)}</div>

    <div id="manipur1" class="">${results.I}</div>
    <div id="manipur2" class="">${results.I}</div>
    <div id="manipur3" class="">${reduceTo22(results.G + results.I)}</div>

    <div id="svadhisthana1" class="">${results.N}</div>
    <div id="svadhisthana2" class="">${results.P}</div>
    <div id="svadhisthana3" class="">${reduceTo22(results.N + results.P)}</div>

    <div id="muladhara1" class="">${results.C}</div>
    <div id="muladhara2" class="">${results.D}</div>
    <div id="muladhara3" class="">${reduceTo22(results.C + results.D)}</div>

    <div id="sum1" class="">${results.healthCardSum1}</div>
    <div id="sum2" class="">${results.healthCardSum2}</div>
    <div id="sum3" class="">${results.healthCardSum3}</div>
`

  circle3.innerHTML = `${reduceTo22(reduceTo22(results.B + results.D) + reduceTo22(results.A + results.C))}`;
  circle1.innerHTML = `${reduceTo22(results.B + results.D)}`;
  circle2.innerHTML = `${reduceTo22(results.A + results.C)}`;

  circle6.innerHTML = `${reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H))}`;
  circle4.innerHTML = `${reduceTo22(results.E + results.G)}`;
  circle5.innerHTML = `${reduceTo22(results.F + results.H)}`;

  circle9.innerHTML = `${reduceTo22(reduceTo22(reduceTo22(results.B + results.D) + reduceTo22(results.A + results.C)) + reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H)))}`;
  circle7.innerHTML = `${reduceTo22(reduceTo22(results.B + results.D) + reduceTo22(results.A + results.C))}`;
  circle8.innerHTML = `${reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H))}`;

  circle12.innerHTML = `${reduceTo22(reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H)) + reduceTo22(reduceTo22(reduceTo22(results.B + results.D) + reduceTo22(results.A + results.C)) + reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H))))}`;
  circle10.innerHTML = `${reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H))}`;
  circle11.innerHTML = `${reduceTo22(reduceTo22(reduceTo22(results.B + results.D) + reduceTo22(results.A + results.C)) + reduceTo22(reduceTo22(results.E + results.G) + reduceTo22(results.F + results.H)))}`;

  diamondSoul.innerHTML = `<span class="life-map__circle">${results.healthCardSum2}</span> <span class="life-map__circle">${results.healthCardSum2}</span> <span class="life-map__circle">${reduceTo22(results.healthCardSum2 + results.healthCardSum2)}</span>`
  brandCodeCircle.innerHTML = `${results.T4}`;



  h2_result.scrollIntoView({ behavior: "smooth" });
});

// Бургер меню
const burger = document.getElementById("burger");
const navMenu = document.getElementById("navMenu");

burger.addEventListener("click", () => {
  burger.classList.toggle("active");
  navMenu.classList.toggle("active");
});

// Закриваємо меню при кліку на посилання
document.querySelectorAll(".nav__list a").forEach(link => {
  link.addEventListener("click", () => {
    burger.classList.remove("active");
    navMenu.classList.remove("active");
  });
});

// Закриваємо меню при кліку поза ним
document.addEventListener("click", (e) => {
  if (!burger.contains(e.target) && !navMenu.contains(e.target)) {
    burger.classList.remove("active");
    navMenu.classList.remove("active");
  }
});


/*************РОЗРАХУНОК****************/

function reduceTo22(num) {
  while (num > 22) {
    num = num
      .toString()
      .split("")
      .reduce((sum, d) => sum + parseInt(d), 0);
  }
  return num;
}

function calculateResults(dateStr) {
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return null;

  const A = reduceTo22(date.getDate());
  const B = reduceTo22(date.getMonth() + 1);
  const year = date.getFullYear();
  const C = reduceTo22(
    year.toString().split("").reduce((sum, d) => sum + parseInt(d), 0)
  );

  const D = reduceTo22(A + B + C);

  const E = reduceTo22(A + B);
  const F = reduceTo22(B + C);
  const G = reduceTo22(C + D);
  const H = reduceTo22(D + A);
  const I = reduceTo22(A + B + C + D);

  const I1 = reduceTo22(E + G + F + H);
  const N = reduceTo22(C + I);
  const J = reduceTo22(A + I);
  const O = reduceTo22(G + I1);
  const K = reduceTo22(E + I1);
  const P = reduceTo22(D + I);
  const L = reduceTo22(B + I);
  const Q = reduceTo22(H + I1);
  const M = reduceTo22(F + I1);
  const I2 = reduceTo22(I + I1);

  const J1 = reduceTo22(A + J);
  const N1 = reduceTo22(C + N);
  const K1 = reduceTo22(E + K);
  const O1 = reduceTo22(G + O);
  const L1 = reduceTo22(B + L);
  const P1 = reduceTo22(D + P);
  const M1 = reduceTo22(F + M);
  const Q1 = reduceTo22(H + Q);
  const R = reduceTo22(I + J);
  const S = reduceTo22(I + L);
  const T = reduceTo22(I + N);

  const T1 = reduceTo22(I + P);
  const T2 = reduceTo22(N + P);
  const T3 = reduceTo22(T2 + P);
  const T4 = reduceTo22(T2 + N);

  const healthCardSum1 = reduceTo22(A + J1 + J + R + I + N + C);
  // const healthCardSum2 = reduceTo22(B + L1 + L + S + I + P + D);
  const healthCardSum2 = processNumber(B + L1 + L + S + I + P + D);
  const healthCardSum3 = reduceTo22(reduceTo22(reduceTo22(A + B) + reduceTo22(J1 + L1) + reduceTo22(J + L) + reduceTo22(R + S) + reduceTo22(I + I) + reduceTo22(N + P) + reduceTo22(C + D)));



  return {
    A, B, C, D,
    E, F, G, H, I,
    I1, N, J, O, K, P, L, Q, M, I2,
    J1, N1, K1, O1, L1, P1, M1, Q1, R, S, T, T1, T2, T3, T4, healthCardSum1, healthCardSum2, healthCardSum3
  };
}
