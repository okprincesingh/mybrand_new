/***************************************************
==================== JS INDEX ======================
01. Data Background Set

****************************************************/

(function ($) {

  "use strict";

  /*-----------------------------------
           Set Background Image & Mask   
        -----------------------------------*/
  if (typeof $ !== "undefined") {
    if ($("[data-bg-src]").length > 0) {
      $("[data-bg-src]").each(function () {
        var src = $(this).attr("data-bg-src");
        $(this).css("background-image", "url(" + src + ")");
        $(this).removeAttr("data-bg-src").addClass("background-image");
      });
    }
  }

  if ($("[data-mask-src]").length > 0) {
    $("[data-mask-src]").each(function () {
      var mask = $(this).attr("data-mask-src");
      $(this).css({
        "mask-image": "url(" + mask + ")",
        "-webkit-mask-image": "url(" + mask + ")",
      });
      $(this).addClass("bg-mask");
      $(this).removeAttr("data-mask-src");
    });
  }

  // Wow js
  new WOW().init();




  //>> Brand Slider Start <<//
  const brandSlider = new Swiper(".brand-slider", {
    spaceBetween: 30,
    speed: 1300,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false,
    },

    breakpoints: {
      1199: {
        slidesPerView: 6,
      },
      991: {
        slidesPerView: 4,
      },
      767: {
        slidesPerView: 4,
      },
      575: {
        slidesPerView: 3,
      },
      0: {
        slidesPerView: 2,
      },
    },
  });

  //>> Blog Slider Start <<//
  const blogSlider = new Swiper(".blog-slider", {
    spaceBetween: 30,
    speed: 1300,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false,
    },

    breakpoints: {
      1199: {
        slidesPerView: 2,
      },
      991: {
        slidesPerView: 2,
      },
      767: {
        slidesPerView: 2,
      },
      575: {
        slidesPerView: 1,
      },
      0: {
        slidesPerView: 1,
      },
    },
    navigation: {
      nextEl: ".blog-area4-wrapper-controls__arrowRight",
      prevEl: ".blog-area4-wrapper-controls__arrowLeft",
    },
  });

  // Intro1 content + image slider (same UI, animated transitions)
  if (document.querySelector(".intro1-slider")) {
    const intro1Slider = new Swiper(".intro1-slider", {
      loop: true,
      speed: 900,
      effect: "fade",
      fadeEffect: {
        crossFade: true,
      },
      autoplay: {
        delay: 4200,
        disableOnInteraction: false,
      },
      allowTouchMove: true,
      navigation: {
        nextEl: ".intro1-slider__arrow--next",
        prevEl: ".intro1-slider__arrow--prev",
      },
      pagination: {
        el: ".intro1-slider__dots",
        clickable: true,
      },
      on: {
        init: function () {
          const bgText = document.querySelector(".intro1__bg-text");
          if (!bgText) return;
          const idx = this.realIndex || 0;
          const x = (idx % 3 - 1) * 24;
          bgText.style.transform = "translate3d(calc(-50% + " + x + "px), -15%, 0) scale(1)";
          bgText.style.opacity = "1";
        },
        slideChangeTransitionStart: function () {
          const bgText = document.querySelector(".intro1__bg-text");
          if (!bgText) return;
          bgText.classList.add("is-transitioning");
          bgText.style.opacity = "0.55";
          bgText.style.transform = "translate3d(-50%, -15%, 0) scale(1.04)";
        },
        slideChangeTransitionEnd: function () {
          const bgText = document.querySelector(".intro1__bg-text");
          if (!bgText) return;
          const idx = this.realIndex || 0;
          const x = (idx % 3 - 1) * 24;
          bgText.classList.remove("is-transitioning");
          bgText.style.opacity = "1";
          bgText.style.transform = "translate3d(calc(-50% + " + x + "px), -15%, 0) scale(1)";
        },
        setTranslate: function () {
          const bgText = document.querySelector(".intro1__bg-text");
          if (!bgText) return;
          const p = Math.max(-1, Math.min(1, this.progress || 0));
          const drift = p * 36;
          bgText.style.transform = "translate3d(calc(-50% + " + drift + "px), -15%, 0) scale(1)";
        },
      },
    });
  }

  // Testimonial Slider 

  var swiper = new Swiper(".testimonial2-slider", {
    loop: true,
    slidesPerView: 1,
    spaceBetween: 20,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      640: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      768: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      1024: {
        slidesPerView: 1,
        spaceBetween: 20,
      },
      1200: {
        slidesPerView: 1,
        spaceBetween: 20,
      },
    },
    navigation: {
      nextEl: ".testimonial2-controls__arrowLeft",
      prevEl: ".testimonial2-controls__arrowRight",
    },
  });

  (function () {
    const reviewsSection = document.querySelector(".reviews-section");
    if (!reviewsSection) return;

    const profileUrls = {
      tp: "https://www.trustpilot.com/review/mybrandplease.com?utm_medium=trustbox&utm_source=TrustBoxReviewCollector",
      goog: "https://www.google.com/search?sca_esv=bb6909982b54b99b&hl=en-IN&sxsrf=ANbL-n6qXDzrzsH5f5lZNI_Ah48QB9jjpw:1779717448312&si=AL3DRZEsmMGCryMMFSHJ3StBhOdZ2-6yYkXd_doETEE1OR-qOY_XvxlYEfuQHA_YuEiHG_72NxKrvskJekSzqy-K2bzweuOLyl7a2xQlxue37ORu3aw8uiLZyOQzvcCNu7AMP3jPY3c1u0N-h67CszFADy_fotsv3Q%3D%3D&q=mybrandplease.com+Reviews&sa=X&ved=2ahUKEwiyorqSzNSUAxVnUGwGHai-Fe8Q0bkNegQIJhAH&biw=2231&bih=969&dpr=2",
      ali: "https://mybrandplease.trustpass.alibaba.com/company_profile/feedback.html?spm=a2700.shop_cp.88.105.2f087bc8WNX5wH"
    };

    const allReviews = [
      { p: "tp", name: "Steve Marc", ini: "SM", ac: "#E6FBF4", tc: "#00875A",  date: "8 Mar 2026", text: "Communication was clear and professional from the beginning, the team stayed responsive, and the products arrived on time with quality that met expectations." },
      { p: "tp", name: "Zain Sheikh", ini: "ZS", ac: "#E6FBF4", tc: "#00875A",  date: "21 Feb 2026", text: "A professional long-term partner with strong expertise across formulation, packaging, design, compliance, and customer service." },
      { p: "tp", name: "Meghana Ghosh", ini: "MG", ac: "#E6FBF4", tc: "#00875A",  date: "15 Feb 2026", text: "MyBrandPlease supported the brand from concept to launch with guidance on ingredients, positioning, compliance, packaging, and market readiness." },
      { p: "tp", name: "Yawovi Yevoudakor", ini: "YY", ac: "#E6FBF4", tc: "#00875A",  date: "16 Oct 2025", text: "Good products, helpful customer service, and a pleasant purchase experience made it easy to return for another order." },
      { p: "tp", name: "Elina", ini: "EL", ac: "#E6FBF4", tc: "#00875A",  date: "11 May 2025", text: "The hair care range delivered top-shelf quality and made launching a new brand feel simple and successful." },
      { p: "goog", name: "Priya Mehta", ini: "PM", ac: "#E8F0FE", tc: "#1A73E8",  date: "9 May 2026", text: "Incredible service from start to finish. They handled formulation and labeling while keeping the MOQ practical for a startup brand." },
      { p: "goog", name: "James Carter", ini: "JC", ac: "#E8F0FE", tc: "#1A73E8",  date: "1 May 2026", text: "Exceptional quality control and a responsive team. The custom formulation matched the brief and gave us confidence to expand the line." },
      { p: "goog", name: "Ananya Joshi", ini: "AJ", ac: "#E8F0FE", tc: "#1A73E8",  date: "24 Apr 2026", text: "The team guided us through each step of the private label process and helped the final products look premium." },
      { p: "goog", name: "Rahul Sharma", ini: "RS", ac: "#E8F0FE", tc: "#1A73E8",  date: "18 Apr 2026", text: "Top quality private label formulations with noticeable customer response after switching to MyBrandPlease." },
      { p: "goog", name: "Nisha Kapoor", ini: "NK", ac: "#E8F0FE", tc: "#1A73E8",  date: "12 Apr 2026", text: "Supportive communication, polished packaging, and dependable timelines made the launch process much smoother." },
      { p: "ali", name: "Li Wei", ini: "LW", ac: "#FFF2E8", tc: "#C25200",  date: "6 May 2026", text: "A strong B2B supplier for private label cosmetics with fast communication and reliable bulk order delivery." },
      { p: "ali", name: "Maria Santos", ini: "MS", ac: "#FFF2E8", tc: "#C25200",  date: "28 Apr 2026", text: "Custom branding was handled well, the products passed quality checks, and the pricing stayed competitive for reorder planning." },
      { p: "ali", name: "Omar Khan", ini: "OK", ac: "#FFF2E8", tc: "#C25200",  date: "19 Apr 2026", text: "Samples, packaging options, and production details were explained clearly, which helped us move forward with confidence." },
      { p: "ali", name: "Sofia Martins", ini: "SM", ac: "#FFF2E8", tc: "#C25200",  date: "10 Apr 2026", text: "The team responded quickly during sourcing and kept the order organized from product selection through dispatch." },
      { p: "ali", name: "Daniel Roberts", ini: "DR", ac: "#FFF2E8", tc: "#C25200",  date: "2 Apr 2026", text: "Reliable supplier experience with clear communication, good packaging quality, and consistent private label support." },
    ];

    const platLogo = {
      tp: "assets/imgs/about/trusti.png",
      goog: "assets/imgs/about/googli.png",
      ali: "assets/imgs/about/aliba.png"
    };
    const platformKey = { tp: "trustpilot", goog: "google", ali: "alibaba" };
    const platformLogo = {
      trustpilot: "assets/imgs/about/trusti.png",
      google: "assets/imgs/about/googli.png",
      alibaba: "assets/imgs/about/aliba.png"
    };
    const platformLabel = {
      trustpilot: "Trustpilot Reviews",
      google: "Google Reviews",
      alibaba: "Alibaba Reviews"
    };
    const platLabel = { tp: "Trustpilot", goog: "Google", ali: "Alibaba" };
    const platPill = { tp: "rv-pill-tp", goog: "rv-pill-goog", ali: "rv-pill-ali" };
    const activeClass = { all: "active-all", tp: "active-tp", goog: "active-goog", ali: "active-ali" };
    const scoreLogo = {
      all: "uploads/logo/trusp.png",
      tp: "uploads/logo/trusp.png",
      goog: "uploads/logo/goo.png",
      ali: "uploads/logo/ali.png"
    };
    const scoreLabel = { all: "Trustpilot", tp: "Trustpilot", goog: "Google", ali: "Alibaba" };
    const scoreValue = { all: "4.4", tp: "4.4", goog: "4.8", ali: "4.7" };
    const scoreText = { all: "Excellent", tp: "Excellent", goog: "Excellent", ali: "Excellent" };
    const headingHTML = {
      all: "Here's what our customers say after <b>Excellent</b>",
      tp: "mybrandplease.com is rated <b>Excellent</b>",
      goog: "mybrandplease.com is rated <b>Excellent</b>",
      ali: "mybrandplease.com is rated <b>Excellent</b>"
    };

    allReviews.forEach((review) => {
      review.platform = platformKey[review.p] || review.p;
    });

    let list = [...allReviews];
    let cur = 0;
    let autoT;
    let progT;
    let pg = 0;
    let hasReviewInteraction = false;
    const DUR = 4500;

    function starsHTML(n) {
      n = Number.isFinite(Number(n)) ? Number(n) : 5;
      return "&#9733;".repeat(n) + "&#9734;".repeat(5 - n);
    }

    function esc(value) {
      return String(value).replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;"
      }[char]));
    }

    function updatePlatformBadge(showBadge) {
      const badge = document.getElementById("rvPlatformBadge");
      const logo = document.getElementById("rvPlatformBadgeLogo");
      const activeReview = list[cur];
      if (!badge || !logo || !activeReview) return;

      const platform = activeReview.platform;
      logo.src = platformLogo[platform] || "";
      logo.alt = platformLabel[platform] || "";
      badge.setAttribute("aria-hidden", showBadge ? "false" : "true");
      reviewsSection.classList.toggle("is-review-engaged", Boolean(showBadge));
    }

    function updateScoreCard(platform) {
      const normalized = platform === "goog" || platform === "ali" || platform === "tp" ? platform : "all";
      const card = document.querySelector(".rv-score-card");
      const logo = document.getElementById("rvScoreLogo");
      const name = document.getElementById("rvScoreName");
      const blocks = document.getElementById("rvScoreBlocks");
      const value = document.getElementById("rvScoreValue");
      const text = document.getElementById("rvScoreText");
      if (!logo || !name || !blocks || !value || !text) return;

      const applyScore = () => {
        logo.src = scoreLogo[normalized];
        logo.alt = scoreLabel[normalized];
        name.textContent = scoreLabel[normalized];
        value.textContent = scoreValue[normalized];
        text.textContent = scoreText[normalized];
        blocks.classList.remove("rv-score-card__blocks--tp", "rv-score-card__blocks--goog", "rv-score-card__blocks--ali");
        blocks.classList.add("rv-score-card__blocks--" + (normalized === "all" ? "tp" : normalized));
      };

      if (!card || card.dataset.platform === normalized) {
        applyScore();
        if (card) card.dataset.platform = normalized;
        return;
      }

      card.classList.add("is-changing");
      window.setTimeout(() => {
        applyScore();
        card.dataset.platform = normalized;
        card.classList.remove("is-changing");
      }, 140);
    }

    function animateIntroPanel(platform) {
      const intro = document.getElementById("rvIntroPanel");
      const heading = document.getElementById("rvHeading");
      const normalized = platform === "goog" || platform === "ali" || platform === "tp" ? platform : "all";
      if (!intro || !heading) return;

      intro.classList.remove("is-opening");
      intro.classList.add("is-switching");

      window.setTimeout(() => {
        heading.innerHTML = headingHTML[normalized] || headingHTML.all;
        intro.classList.remove("is-switching");
        intro.classList.add("is-opening");
      }, 170);
    }

    function render() {
      document.getElementById("track").innerHTML = list.map((r) => `
        <div class="rv-card">
          <span class="rv-qmark">"</span>
          <p class="rv-text">${esc(r.text)}</p>
          <div class="rv-footer">
            <div class="rv-reviewer">
              <div class="rv-avatar" style="background:${esc(r.ac)};color:${esc(r.tc)}">${esc(r.ini)}</div>
              <div>
                <p class="rv-name">${esc(r.name)}</p>
                <p class="rv-date">${esc(r.date)}</p>
              </div>
            </div>
            <div class="rv-right">
              <p class="rv-stars">${starsHTML(r.stars)}</p>
              <span class="rv-pill ${platPill[r.p]}">
                <img src="${platLogo[r.p]}" alt="${esc(platLabel[r.p])}" class="rv-platform-logo">
              </span>
            </div>
          </div>
          <a class="rv-readmore" href="${esc(profileUrls[r.p])}" target="_blank" rel="noopener noreferrer">Read more</a>
        </div>
      `).join("");

      document.getElementById("track").style.transform = `translateX(-${cur * 100}%)`;
      document.getElementById("dots").innerHTML = list.map((_, i) =>
        `<div class="rv-dot${i === cur ? " active" : ""}" onclick="goTo(${i})"></div>`
      ).join("");
      updatePlatformBadge(hasReviewInteraction);
      updateScoreCard(list[cur] ? list[cur].p : "all");
    }

    function resetProg() {
      clearInterval(progT);
      clearInterval(autoT);
      pg = 0;
      document.getElementById("pbar").style.width = "0%";
      progT = setInterval(() => {
        pg += 100 / (DUR / 100);
        if (pg > 100) pg = 100;
        document.getElementById("pbar").style.width = pg + "%";
      }, 100);
      autoT = setInterval(() => window.next(false), DUR);
    }

    window.goTo = function (i, revealBadge = true) {
      if (revealBadge) hasReviewInteraction = true;
      cur = i;
      document.getElementById("track").style.transform = `translateX(-${cur * 100}%)`;
      document.querySelectorAll(".rv-dot").forEach((d, j) => {
        d.className = "rv-dot" + (j === cur ? " active" : "");
      });
      updatePlatformBadge(hasReviewInteraction);
      updateScoreCard(list[cur] ? list[cur].p : "all");
      resetProg();
    };

    window.next = function (revealBadge = true) {
      window.goTo((cur + 1) % list.length, revealBadge);
    };

    window.prev = function (revealBadge = true) {
      window.goTo((cur - 1 + list.length) % list.length, revealBadge);
    };

    window.filterPlat = function (p, btn) {
      document.querySelectorAll(".rv-tab").forEach((t) => {
        t.classList.remove("active-all", "active-tp", "active-goog", "active-ali");
      });
      btn.classList.add(activeClass[p]);
      list = p === "all" ? [...allReviews] : allReviews.filter((r) => r.p === p);
      cur = 0;
      hasReviewInteraction = true;
      animateIntroPanel(p);
      updateScoreCard(p);
      render();
      resetProg();
    };

    render();
    resetProg();
  })();


  // Offertwo Slider
  var swiper = new Swiper(".offertwo-slider", {
    loop: true,
    slidesPerView: 1,
    spaceBetween: 20,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      640: {
        slidesPerView: 2,
        spaceBetween: 10,
      },
      768: {
        slidesPerView: 2,
        spaceBetween: 10,
      },
      1024: {
        slidesPerView: 2.5,
        spaceBetween: 20,
      },
      1200: {
        slidesPerView: 3,
        spaceBetween: 20,
      },
    },
    navigation: {
      nextEl: ".offertwo-controls__arrowLeft",
      prevEl: ".offertwo-controls__arrowRight",
    },
  });

  // Instagram Slider
  if ($('.instagram-slider').length > 0) {
    const InstagramSlider = new Swiper(".instagram-slider", {
      spaceBetween: 30,
      speed: 1300,
      loop: true,
      centeredSlides: true,
      autoplay: {
        delay: 2000,
        disableOnInteraction: false,
      },

      breakpoints: {
        1199: {
          slidesPerView: 6,
        },
        991: {
          slidesPerView: 4,
        },
        767: {
          slidesPerView: 3,
        },
        575: {
          slidesPerView: 2,
        },
        0: {
          slidesPerView: 1,
        },
      },
    });
  }

  // Featured-Products
  var swiper = new Swiper(".featured-products-slider", {
    loop: true,
    slidesPerView: 1,
    spaceBetween: 20,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      640: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      768: {
        slidesPerView: 2,
        spaceBetween: 10,
      },
      1024: {
        slidesPerView: 2,
        spaceBetween: 20,
      },
      1200: {
        slidesPerView: 2,
        spaceBetween: 20,
      },
    },
    navigation: {
      nextEl: ".featured-products-controls__arrowRight",
      prevEl: ".featured-products-controls__arrowLeft",
    },
  });

  var swiper = new Swiper(".featured-products2-slider", {
    loop: true,
    slidesPerView: 1,
    spaceBetween: 20,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    breakpoints: {
      320: {
        slidesPerView: 1,
        spaceBetween: 10,
      },
      640: {
        slidesPerView: 2,
        spaceBetween: 10,
      },
      768: {
        slidesPerView: 2,
        spaceBetween: 10,
      },
      1024: {
        slidesPerView: 3,
        spaceBetween: 20,
      },
      1200: {
        slidesPerView: 4,
        spaceBetween: 20,
      },
    },
    navigation: {
      nextEl: ".featured-products2-controls__arrowRight",
      prevEl: ".featured-products2-controls__arrowLeft",
    },
  });

  const clientsSwiper = new Swiper(".clients-line7__slider", {
    loop: true,
    speed: 600,
    autoplay: { delay: 1800, disableOnInteraction: false },
    allowTouchMove: true,
    slidesPerView: 2.8, // mobile baseline
    spaceBetween: 32,
    breakpoints: {
      480: { slidesPerView: 4, spaceBetween: 40 },
      768: { slidesPerView: 6, spaceBetween: 56 },
      992: { slidesPerView: 8, spaceBetween: 64 },
      1200: { slidesPerView: 10, spaceBetween: 72 },
    },
  });

  // Masirul
  /* ---------------------------
    Price range input (DEFENSIVE)
    This was causing: Cannot read properties of null (reading 'addEventListener')
 ---------------------------- */
  (function initPriceRange() {
    var range = document.getElementById("priceRange");
    var output = document.getElementById("priceOutput");

    if (!range && !output) {
      // Neither exists — nothing to do
      return;
    }

    if (!range) {
      return;
    }
    if (!output) {
      return;
    }

    // Safe to attach listener
    range.addEventListener("input", function () {
      output.value = "Price: $0 — $" + range.value;
    });
  })();

  /* ---------------------------
     Quantity (DEFENSIVE)
  ---------------------------- */
  (function initQuantityControls() {
    var qtyInput = document.getElementById("quantity");
    var inc = document.getElementById("increase");
    var dec = document.getElementById("decrease");

    if (!qtyInput) {
      // Not an error: maybe cart markup isn't on this page
      // console.info('No #quantity input found on this page.');
      return;
    }

    // Ensure numeric default
    if (!qtyInput.value) qtyInput.value = "1";

    if (inc) {
      inc.addEventListener("click", function () {
        var v = parseInt(qtyInput.value || "0", 10) || 0;
        qtyInput.value = v + 1;
        qtyInput.dispatchEvent(new Event("input"));
      });
    } else {
      // optional: console.warn('Increase button (#increase) not found.');
    }

    if (dec) {
      dec.addEventListener("click", function () {
        var v = parseInt(qtyInput.value || "0", 10) || 0;
        if (v > 1) {
          qtyInput.value = v - 1;
          qtyInput.dispatchEvent(new Event("input"));
        }
      });
    }
  })();

  // Cart calculations (corrected & defensive)
  // Helper: format as currency
  // Change the currency symbol or locale formatting if you want (e.g. useIntl.NumberFormat).
  function money(n) {
    var num = Number(n || 0);
    if (isNaN(num)) num = 0;
    // returns e.g. $12.34 — change '$' to '৳' for BDT or use Intl.NumberFormat for localized output
    return "$" + num.toFixed(2);
  }

  var $cartBody = $(".cart-page__body");
  var $subtotalEl = $("#cart-subtotal");
  var $shippingEl = $("#cart-shipping");
  var $totalEl = $("#cart-total");
  var $updateBtn = $(".cart-page__update-btn");
  var $couponInput = $(".cart-page__coupon-input");
  var $couponBtn = $(".cart-page__coupon-btn");
  var $couponMsg = $(".cart-page__coupon-msg");
  var $checkoutBtn = $("#proceed-checkout");

  var SHIPPING = 8.0;
  var coupon = null;
  var appliedDiscount = 0;

  var COUPONS = {
    SAVE10: { type: "percent", value: 10 },
    FLAT5: { type: "flat", value: 5 },
  };

  function parseUnitPrice(raw) {
    // Accept numbers or strings like "12.34" or "1,234.56"
    if (raw == null) return 0;
    var s = String(raw).replace(/,/g, "").trim();
    var v = parseFloat(s);
    return isNaN(v) ? 0 : v;
  }

  function recalcRow($row) {
    var unit = parseUnitPrice($row.data("unit-price"));
    var $qtyInput = $row.find(".cart-page__qty-input");
    var qty = parseInt($qtyInput.val(), 10);
    if (isNaN(qty) || qty < 1) {
      qty = 1;
      $qtyInput.val(1);
    }
    var total = unit * qty;
    var $totalCell = $row.find(".cart-page__total");
    if ($totalCell.length) $totalCell.text(money(total));
    return total;
  }

  function recalcAll() {
    if ($cartBody.length === 0) return;

    var subtotal = 0;
    $cartBody.find(".cart-page__item").each(function () {
      subtotal += recalcRow($(this));
    });

    appliedDiscount = 0;
    if (coupon) {
      if (coupon.type === "percent") {
        appliedDiscount = subtotal * (coupon.value / 100);
      } else if (coupon.type === "flat") {
        appliedDiscount = coupon.value;
      }
    }

    // Prevent discount from exceeding subtotal
    appliedDiscount = Math.min(appliedDiscount, subtotal);

    var subtotalAfter = Math.max(0, subtotal - appliedDiscount);
    var shipping = subtotalAfter > 0 ? SHIPPING : 0;
    var total = subtotalAfter + shipping;

    if ($subtotalEl.length) $subtotalEl.text(money(subtotalAfter));
    if ($shippingEl.length) $shippingEl.text(money(shipping));
    if ($totalEl.length) $totalEl.text(money(total));

    if ($couponMsg.length) {
      if (coupon && appliedDiscount > 0) {
        $couponMsg.text(
          'Coupon "' +
          coupon.code +
          '" applied: -' +
          money(appliedDiscount) +
          "."
        );
      } else if (coupon && appliedDiscount === 0) {
        // coupon was found but no discount (e.g., subtotal 0)
        $couponMsg.text(
          'Coupon "' + coupon.code + '" applied but discount is 0.'
        );
      } else {
        $couponMsg.text("");
      }
    }
  }

  // Event delegation for cart controls (robust if items added dynamically)
  if ($cartBody.length) {
    $cartBody.on("click", function (e) {
      var $t = $(e.target);

      // minus
      var $minus = $t.closest(".cart-page__qty-btn--minus");
      if ($minus.length) {
        var $row = $minus.closest(".cart-page__item");
        var $input = $row.find(".cart-page__qty-input");
        var v = parseInt($input.val() || "1", 10);
        $input.val(Math.max(1, v - 1)).trigger("input");
        recalcAll();
        return;
      }

      // plus
      var $plus = $t.closest(".cart-page__qty-btn--plus");
      if ($plus.length) {
        var $rowp = $plus.closest(".cart-page__item");
        var $inputp = $rowp.find(".cart-page__qty-input");
        var vp = parseInt($inputp.val() || "1", 10);
        $inputp.val(Math.max(1, vp + 1)).trigger("input");
        recalcAll();
        return;
      }

      // remove
      var $remove = $t.closest(".cart-page__remove");
      if ($remove.length) {
        var $rowr = $remove.closest(".cart-page__item");
        $rowr.remove();
        recalcAll();
        return;
      }
    });

    // typed qty
    $cartBody.on("input", ".cart-page__qty-input", function () {
      var $input = $(this);
      var val = $input.val();
      if (val === "") return;
      var n = parseInt(val, 10);
      if (isNaN(n) || n < 1) $input.val(1);
      recalcAll();
    });
  }

  // update cart button
  if ($updateBtn.length) {
    $updateBtn.on("click", function () {
      recalcAll();
      var $btn = $(this);
      var original = $btn.text();
      $btn.text("Updated");
      setTimeout(function () {
        $btn.text(original);
      }, 800);
    });
  }

  // coupon
  if ($couponBtn.length) {
    $couponBtn.on("click", function () {
      var code = ($couponInput.val() || "").trim().toUpperCase();
      if (!code) {
        if ($couponMsg.length) $couponMsg.text("Please enter a coupon code.");
        return;
      }
      if (COUPONS.hasOwnProperty(code)) {
        coupon = $.extend({ code: code }, COUPONS[code]);
        recalcAll();
      } else {
        coupon = null;
        if ($couponMsg.length) $couponMsg.text("Invalid coupon code.");
        recalcAll();
      }
    });
  }

  if ($checkoutBtn.length) {
    $checkoutBtn.on("click", function () {
      window.location.href = "/checkout";
    });
  }

  // initial calc (only if cart body exists)
  if ($cartBody.length) recalcAll();

  // =======================
  //  Testimonial Slider
  // =======================

  // Define hasSwiper BEFORE using it
  var hasSwiper = typeof Swiper !== "undefined";

  // Testimonial slider (only init if both Swiper present and element exists)
  if (hasSwiper && document.querySelector(".testimonial-area8__active")) {
    try {
      new Swiper(".testimonial-area8__active", {
        loop: true,
        speed: 800,
        slidesPerView: 1,
        spaceBetween: 20,
        observer: true,
        observeParents: true,
        autoplay: { delay: 3000, disableOnInteraction: false },
        breakpoints: {
          320: { slidesPerView: 1, spaceBetween: 10 },
          640: { slidesPerView: 1, spaceBetween: 10 },
          768: { slidesPerView: 2, spaceBetween: 12 },
          1024: { slidesPerView: 2, spaceBetween: 20 },
          1200: { slidesPerView: 2, spaceBetween: 24 },
        },
      });
    } catch (err) {
      // Testimonial slider init failed
    }
  }

  if (document.querySelector(".project-section-3__active")) {
    var swiper = new Swiper(".project-section-3__active", {
      slidesPerView: 3,
      spaceBetween: 30,
      loop: true,
      centeredSlides: false,
      autoplay: true,
      centerMode: true,
      speed: 400,
      pagination: {
        el: ".project-section-3__pagination",
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        767: {
          slidesPerView: 1.5,
        },
        992: {
          slidesPerView: 2,
        },
        1200: {
          slidesPerView: 3,
        },
      },
    });
  }

  if (document.querySelector(".project-section-3__active")) {
    var swiper = new Swiper(".project-section-3__active", {
      slidesPerView: 3,
      spaceBetween: 30,
      loop: true,
      centeredSlides: false,
      autoplay: true,
      centerMode: true,
      speed: 400,
      pagination: {
        el: ".project-section-3__pagination",
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        767: {
          slidesPerView: 1.5,
        },
        992: {
          slidesPerView: 2,
        },
        1200: {
          slidesPerView: 3,
        },
      },
    });
  }

  if (document.querySelector(".testimonial-section-3__active")) {
    var swiper = new Swiper(".testimonial-section-3__active", {
      slidesPerView: 3,
      spaceBetween: 30,
      loop: true,
      centeredSlides: false,
      autoplay: true,
      centerMode: true,
      speed: 400,
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        767: {
          slidesPerView: 1.5,
        },
        992: {
          slidesPerView: 2,
        },
        1200: {
          slidesPerView: 3,
        },
      },
    });
  }

  if (document.querySelector(".team-3__active")) {
    var swiper = new Swiper(".team-3__active", {
      slidesPerView: 5,
      spaceBetween: 30,
      loop: true,
      centeredSlides: true,
      autoplay: true,
      centerMode: true,
      speed: 400,
      breakpoints: {
        320: {
          slidesPerView: 1,
          centeredSlides: false,
        },
        767: {
          slidesPerView: 1,
          centeredSlides: false,
        },
        992: {
          slidesPerView: 2,
          centeredSlides: false,
        },
        1200: {
          slidesPerView: 5,
        },
      },
    });
  }

  /* === testimonial-5__active (index 05) === */
  if ($(".testimonial-5__active").length > 0) {
    var design_showcase = new Swiper(".testimonial-5__active", {
      loop: true,
      speed: 2000,
      // autoplay: {
      //   delay: 2500,
      // },
      slidesPerView: 1,
      spaceBetween: 20,
      centeredSlides: true,
      navigation: {
        prevEl: ".testimonial-5__swiper-button-prev",
        nextEl: ".testimonial-5__swiper-button-next",
      },

      breakpoints: {
        0: {
          slidesPerView: 1,
          spaceBetween: 10,
          centeredSlides: true,
        },
        576: {
          slidesPerView: 1,
          spaceBetween: 15,
        },
        768: {
          slidesPerView: 1,
          spaceBetween: 20,
        },
        1200: {
          slidesPerView: 1,
          spaceBetween: 20,
        },
      },
    });
  }

  // odometer js
  // Check if GSAP and ScrollTrigger are available
  if (typeof gsap !== "undefined" && typeof ScrollTrigger !== "undefined") {
    gsap.registerPlugin(ScrollTrigger);

    // Check if there are any odometer elements
    const odometers = document.querySelectorAll(".odometer");
    if (odometers.length > 0) {
      odometers.forEach((el) => {
        const count = el.getAttribute("data-count");

        ScrollTrigger.create({
          trigger: el,
          start: "top 90%", // when element enters 90% viewport height
          once: true, // trigger only once
          onEnter: () => {
            el.innerHTML = count; // this change triggers odometer animation
          },
        });
      });
    }
  } else {
    console.warn(
      "GSAP or ScrollTrigger not found. Odometer animation skipped."
    );
  }

  // brand - section - 5
  if (document.querySelector(".brand-section-5__active")) {
    document.addEventListener("DOMContentLoaded", function () {
      const swiper = new Swiper(".brand-section-5__active", {
        slidesPerView: "6",
        spaceBetween: 20,
        centeredSlides: false,
        speed: 3500,
        loop: true,
        freeMode: false,
        allowTouchMove: false,
        autoplay: {
          delay: 1,
        },
        breakpoints: {
          320: {
            spaceBetween: 20,
            slidesPerView: "3",
          },
          576: {
            spaceBetween: 20,
            slidesPerView: "4",
          },
          767: {
            spaceBetween: 20,
            slidesPerView: "4",
          },
          992: {
            spaceBetween: 20,
          },
          1200: {
            spaceBetween: 20,
          },
        },
      });
    });
  }

  // accordion js
  document.querySelectorAll(".accordion-item").forEach((item) => {
    let number = item.querySelector(".accordion-number");
    let collapse = item.querySelector(".accordion-collapse");

    collapse.addEventListener("shown.bs.collapse", function () {
      number.style.display = "block";
    });

    collapse.addEventListener("hidden.bs.collapse", function () {
      number.style.display = "none";
    });
  });

  // Make sure GSAP and ScrollTrigger are loaded
  gsap.registerPlugin(ScrollTrigger);

  $(".popup-video").magnificPopup({
    type: "iframe",
  });

  if (document.querySelector(".brand-slide__active")) {
    new Swiper(".brand-slide__active", {
      slidesPerView: "auto",
      spaceBetween: 20,
      speed: 3000,
      loop: true,
      allowTouchMove: false,
      autoplay: { delay: 1 },
    });
  }

  if (document.querySelector(".footer-text-slide__active")) {
    new Swiper(".footer-text-slide__active", {
      slidesPerView: "auto",
      speed: 8000,
      loop: true,
      allowTouchMove: false,
      autoplay: { delay: 1 },
    });
  }

  if (document.querySelector(".text-slide__active")) {
    new Swiper(".text-slide__active", {
      slidesPerView: "auto",
      speed: 9000,
      loop: true,
      allowTouchMove: false,
      autoplay: { delay: 1 },
    });
  }

  if (document.querySelectorAll(".milestone-2__active").length > 0) {
    var milestone_2_active = new Swiper(".milestone-2__active", {
      slidesPerView: 1,
      spaceBetween: 5,
      speed: 2000,
      loop: true,
      autoplay: true,
      speed: 600,
      watchSlidesProgress: true,
      navigation: {
        prevEl: ".milestone-2-prev",
        nextEl: ".milestone-2-next",
      },
      breakpoints: {
        576: {
          slidesPerView: 1,
        },
        768: {
          slidesPerView: 2,
        },
        992: {
          slidesPerView: 3,
        },
        1201: {
          slidesPerView: 4,
        },
        1400: {
          slidesPerView: 4,
        },
      },
    });
  }

  // testimonial 3 active rana
  if (document.querySelectorAll(".expertise-2-active").length > 0) {
    var expertise_activee = new Swiper(".expertise-2-active", {
      loop: true,
      slidesPerView: 3,
      spaceBetween: 27,
      speed: 2000,
      autoplay: true,
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        576: {
          slidesPerView: 2,
        },
        768: {
          slidesPerView: 2,
        },
        992: {
          slidesPerView: 2,
        },
        1201: {
          slidesPerView: 3,
        },
        1367: {
          slidesPerView: 4,
        },
      },
    });
  }

  // blog-active rana
  if (document.querySelectorAll(".blog-active").length > 0) {
    var blog_active = new Swiper(".blog-active", {
      loop: true,
      slidesPerView: 1,
      spaceBetween: 125,
      speed: 2000,
      pagination: {
        el: ".swiper-pagination",
        type: "progressbar",
      },

      breakpoints: {
        320: {
          slidesPerView: 1,
          spaceBetween: 25,
        },
        576: {
          slidesPerView: 1,
          spaceBetween: 25,
        },
        768: {
          slidesPerView: 1,
          spaceBetween: 25,
        },
        992: {
          slidesPerView: 1,
          spaceBetween: 25,
        },
        1200: {
          slidesPerView: 2,
          spaceBetween: 25,
        },
        1367: {
          slidesPerView: 2,
        },
      },
    });
  }

  //>> Brand Slider Start <<//
  const brandSlider1 = new Swiper(".brand-slider", {
    spaceBetween: 30,
    speed: 1300,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false,
    },

    breakpoints: {
      1199: {
        slidesPerView: 6,
      },
      991: {
        slidesPerView: 4,
      },
      767: {
        slidesPerView: 4,
      },
      575: {
        slidesPerView: 3,
      },
      0: {
        slidesPerView: 2,
      },
    },
  });

  const brandSlider6 = new Swiper(".brand-slider6", {
    spaceBetween: 30,
    speed: 1300,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false,
    },

    breakpoints: {
      1199: {
        slidesPerView: 7,
      },
      991: {
        slidesPerView: 5,
      },
      767: {
        slidesPerView: 4,
      },
      575: {
        slidesPerView: 3,
      },
      0: {
        slidesPerView: 2,
      },
    },
  });

  //>> Blog Slider Start <<//
  const blogSlider1 = new Swiper(".blog-slider", {
    spaceBetween: 30,
    speed: 1300,
    loop: true,
    centeredSlides: true,
    autoplay: {
      delay: 2000,
      disableOnInteraction: false,
    },

    breakpoints: {
      1440: {
        slidesPerView: 2,
      },
      1199: {
        slidesPerView: 1,
      },
      991: {
        slidesPerView: 1,
      },
      767: {
        slidesPerView: 1,
      },
      575: {
        slidesPerView: 1,
      },
      0: {
        slidesPerView: 1,
      },
    },
    navigation: {
      nextEl: ".blog-area4-wrapper-controls__arrowRight",
      prevEl: ".blog-area4-wrapper-controls__arrowLeft",
    },
  });

  // testimonials-section rana
  if ($(".pin-area-3").length > 0) {
    let mm = gsap.matchMedia();
    mm.add("(min-width: 768px)", () => {
      return gsap.to(".pin-element_3", {
        scrollTrigger: {
          trigger: ".pin-area-3",
          scrub: 1,
          start: "top top",
          end: "bottom bottom",
          pin: ".pin-element_3",
          pinSpacing: false,
          markers: false,
          toggleActions: "play reverse play reverse",
        },
      });
    });
  }

  if ($(".Projects-area-10").length > 0) {
    let mm = gsap.matchMedia();

    mm.add("(min-width: 768px)", () => {
      gsap.to(".Projects-area-10", {
        opacity: 1,
        scrollTrigger: {
          trigger: ".Projects-area-10",
          start: "top top",
          end: "bottom 100%",
          scrub: 1,
          pin: ".Projects__content",
          pinSpacing: false,
          toggleActions: "play reverse play reverse",
        },
      });

      gsap.to(".recent-work-2__box", {
        scrollTrigger: {
          trigger: ".Projects-area-10",
          start: "top top",
          end: "bottom 100%",
          scrub: 1,
          pin: ".recent-work-2__box",
          pinSpacing: false,
          toggleActions: "play reverse play reverse",
        },
      });
    });
  }

  if (document.querySelector(".Projects-area-10") && window.innerWidth > 768) {
    const projectArea = document.querySelector(".Projects-area-10");
    const steps = document.querySelectorAll(".Projects__content ul li");
    const stepCount = steps.length;

    const fill = document.querySelector(".Projects-area-10__fill");
    const current = document.getElementById("Projects-area-10__current");
    const total = document.getElementById("Projects-area-10__total");

    if (total) total.textContent = String(stepCount).padStart(2, "0");

    ScrollTrigger.create({
      trigger: projectArea,
      start: "top top",
      end: "bottom bottom",
      scrub: true,
      onUpdate: ({ progress }) => {
        const step = Math.min(
          stepCount,
          Math.max(1, Math.floor(progress * (stepCount - 1)) + 1)
        );
        const width = (step / stepCount) * 100;

        if (fill) fill.style.width = `${width}%`;
        if (current) current.textContent = String(step).padStart(2, "0");

        steps.forEach((li, index) => {
          li.classList.toggle("active", index + 1 === step);
        });
      },
    });
  }



  document.querySelectorAll('.qty-pill').forEach(pill => {
    const row = pill.closest('.cart-row');
    const totalEl = row.querySelector('.c-total');
    const price = Number(pill.dataset.price || 0);

    const render = () => {
      const q = Number(pill.querySelector('.q-val').textContent.trim() || 0);
      totalEl.textContent = '$' + (price * q).toFixed(2);
    };

    pill.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-action]');
      if (!btn) return;

      const valEl = pill.querySelector('.q-val');
      let q = Number(valEl.textContent.trim() || 0);

      if (btn.dataset.action === 'inc') q += 1;
      if (btn.dataset.action === 'dec') q = Math.max(1, q - 1);

      valEl.textContent = String(q);
      render();
    });

    render();
  });

  // Remove row
  document.querySelectorAll('.btn-x').forEach(x => {
    x.addEventListener('click', () => x.closest('.cart-row')?.remove());
  });

  //>> Search Popup Start <<//
  const $searchWrap = $(".search-wrap");
  const $navSearch = $(".nav-search");
  const $searchClose = $("#search-close");

  if ($searchWrap.length) {
    $(".search-trigger").on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      $searchWrap.animate({ opacity: "toggle" }, 500);
      $navSearch.add($searchClose).addClass("open");
    });

    $searchWrap.find(".search-close").add($searchClose).on("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      $searchWrap.animate({ opacity: "toggle" }, 500);
      $navSearch.add($searchClose).removeClass("open");
    });

    function closeSearch() {
      $searchWrap.fadeOut(200);
      $navSearch.add($searchClose).removeClass("open");
    }

    $(document.body).on("click", function () {
      closeSearch();
    });

    $(".search-trigger, .main-search-input, .search-wrap").on("click", function (e) {
      e.stopPropagation();
    });
  }


  // register ScrollTrigger (uncomment other plugins if you use them elsewhere)
  if (document.querySelector(".section-item")) {
    // Use GSAP matchMedia for responsive control
    const mm = gsap.matchMedia();

    // We'll only create pin triggers for desktop screens (>=1024px)
    mm.add("(min-width: 1024px)", () => {
      const pinList = Array.from(document.querySelectorAll(".section-item"));
      const createdTriggers = [];

      pinList.forEach((item) => {
        // Ensure the item has some height — otherwise pin behaves unexpectedly.
        // If your item has images that load after JS runs, consider running this after imagesLoaded.
        const endDistance = Math.max(item.offsetHeight, window.innerHeight);

        // Create a ScrollTrigger directly (no gsap.to tween needed)
        const st = ScrollTrigger.create({
          trigger: item,
          start: "top top", // pin when top of item reaches top of viewport
          end: () => `+=${endDistance}`, // pin for the height of the element or viewport
          pin: true,
          pinSpacing: false, // set true if you want spacing preserved
          markers: false, // set true while debugging
        });

        createdTriggers.push(st);
      });

      // Return cleanup function that matchMedia will call when leaving this query
      return () => {
        createdTriggers.forEach((t) => t.kill());
      };
    });

    // Optionally add a rule for smaller screens to ensure nothing runs there
    mm.add("(max-width: 1023px)", () => {
      // no pinning on small screens — just return a noop cleanup
      return () => { };
    });
  }

  // Img zoom
  // Guard: run only if gsap + ScrollTrigger are loaded
  if (typeof gsap === "undefined" || typeof ScrollTrigger === "undefined") {
    return;
  }

  gsap.registerPlugin(ScrollTrigger);

  const zoomThumb = document.querySelector(".zoom-thumb");
  const zoomPin = document.querySelector(".zoom-pin");

  if (!zoomThumb) {
    return;
  }

  if (!zoomPin) {
    // zoom-pin not found; ScrollTrigger may use fallback
  }

  // Responsive ScrollTrigger animations (mutually exclusive ranges)
  ScrollTrigger.matchMedia({
    // 1921px and up
    "(min-width: 1921px)": function () {
      gsap.to(zoomThumb, {
        scrollTrigger: {
          trigger: ".zoom-pin",
          start: "top 70%",
          end: "bottom -20%",
          scrub: true,
          // markers: true, // enable while tuning
        },
        x: "-90%",
        y: "100%",
        scale: 6,
        width: "3840px",
        height: "220px",
        borderRadius: "0px",
        paddingLeft: "0px",
        ease: "power1.out",
      });
    },

    // Between 1396px and 1920px (inclusive)
    "(min-width: 1396px) and (max-width: 1920px)": function () {
      gsap.to(zoomThumb, {
        scrollTrigger: {
          trigger: ".zoom-pin",
          start: "top 70%",
          end: "bottom -10%",
          scrub: true,
          // markers: true,
        },
        x: "-110%",
        y: "305%",
        scale: 4,
        width: "1920px",
        height: "205px",
        borderRadius: "0px",
        paddingLeft: "0px",
        ease: "power1.out",
      });
    },

    // 1395px and below — disable animation / reset styles
    "(max-width: 1439px)": function () {
      // Remove any GSAP-applied inline styles so element falls back to CSS
      gsap.set(zoomThumb, { clearProps: "all" });
      // If you want to do a small fallback animation instead of disabling, add it here
    },
  });
})(jQuery);

// ===========================================================================
// Petly Header Functions – Vanilla JS (no jQuery dependency)
// Sticky header, search panel, mobile menu / sidebar
// ===========================================================================
(function () {
  "use strict";

  // --- DOM helpers ---
  function _$(sel, ctx) {
    return (ctx || document).querySelector(sel);
  }
  function _$$(sel, ctx) {
    return Array.from((ctx || document).querySelectorAll(sel));
  }

  // --- 1. Sticky header ---
  function initStickyHeader() {
    var header = _$(".header-sticky");
    if (!header) return;
    var lastScrollTop = 0;
    window.addEventListener("scroll", function () {
      var st = window.scrollY || document.documentElement.scrollTop;
      if (st > lastScrollTop) {
        header.classList.remove("sticky");
        header.classList.add("transformed");
      } else if (st <= 500) {
        header.classList.remove("sticky", "transformed");
      } else {
        header.classList.add("sticky");
        header.classList.remove("transformed");
      }
      lastScrollTop = st;
    }, { passive: true });
  }

  // --- 2. Search panel ---
  function initSearch() {
    var wrap = _$(".header__search");
    if (!wrap) return;
    var openBtn = _$(".search-open-btn");
    var panel = document.getElementById("site-search");
    var inner = panel && _$(".search-inner", panel);
    var input = panel && _$(".search-input", panel);
    var closeBtn = panel && _$(".search-close", panel);
    var backdrop = panel && _$(".search-backdrop", panel);
    var KEY_ESC = 27;

    function openSearch() {
      panel.classList.add("open");
      if (openBtn) openBtn.setAttribute("aria-expanded", "true");
      panel.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
      if (input) setTimeout(function () { input.focus(); }, 180);
    }
    function closeSearch() {
      panel.classList.remove("open");
      if (openBtn) {
        openBtn.setAttribute("aria-expanded", "false");
        openBtn.focus();
      }
      panel.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }

    if (openBtn) {
      openBtn.addEventListener("click", function (e) {
        e.preventDefault();
        if (panel.classList.contains("open")) closeSearch();
        else openSearch();
      });
    }
    if (closeBtn) closeBtn.addEventListener("click", function (e) { e.preventDefault(); closeSearch(); });
    if (backdrop) backdrop.addEventListener("click", closeSearch);
    if (inner) inner.addEventListener("click", function (e) { e.stopPropagation(); });
    document.addEventListener("keydown", function (e) {
      if (e.keyCode === KEY_ESC && panel && panel.classList.contains("open")) closeSearch();
    });
  }

  // --- 3. Mobile menu (sidebar panel) ---
  // Handled by common.js (side-toggle handler) and meanmenu plugin
  // No additional handler needed here

  // --- Init on DOM ready ---
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      initStickyHeader();
      initSearch();
    });
  } else {
    initStickyHeader();
    initSearch();
  }
})();
