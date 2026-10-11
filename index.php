<?php
// ============================================================
// Página principal — PHP puro: lee y muestra los datos directo
// desde MySQL, sin JavaScript de por medio para el backend.
// ============================================================
require 'api/config.php';

$sort = (isset($_GET['sort']) && $_GET['sort'] === 'liked') ? 'liked' : 'recent';
$orderBy = $sort === 'liked' ? 'likes DESC, created_at DESC' : 'created_at DESC';

$photos = $pdo->query(
  "SELECT id, name, caption, filename, likes, UNIX_TIMESTAMP(created_at) AS created_at
     FROM photos ORDER BY $orderBy"
)->fetchAll();

$uploadedCount = isset($_GET['uploaded']) ? (int) $_GET['uploaded'] : null;
$uploadError = $_GET['error'] ?? null;

// Para el lightbox (siguiente/anterior), le pasamos al JS los datos
// que YA trajimos de MySQL — no vuelve a pedirlos por separado.
$photosForJs = array_map(function ($p) {
  return [
    'id' => (int) $p['id'],
    'name' => $p['name'],
    'caption' => $p['caption'],
    'url' => UPLOAD_URL . $p['filename'],
    'likes' => (int) $p['likes'],
  ];
}, $photos);
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Xitlally &amp; Robert — Nuestra Boda</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap"
    rel="stylesheet">
  <style>
    :root {
      --ivory: #F8F4EA;
      --ivory-deep: #EFE8D6;
      --sage: #8B9A7B;
      --sage-soft: #A6B394;
      --sage-deep: #5C6B4E;
      --olive-dark: #38402E;
      --gold: #B99A5B;
      --gold-light: #D9C495;
      --ink: #33352B;
      --white: #FFFFFF;
      --whatsapp: #25D366;
      --shadow: 0 20px 60px rgba(56, 64, 46, 0.12);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      background: var(--ivory);
      color: var(--ink);
      font-family: 'Jost', sans-serif;
      font-weight: 300;
      overflow-x: hidden;
    }

    h1,
    h2,
    h3,
    .script {
      font-family: 'Playfair Display', serif;
      color: var(--olive-dark);
      font-weight: 500;
    }

    .eyebrow {
      font-family: 'Jost', sans-serif;
      letter-spacing: 0.35em;
      text-transform: uppercase;
      font-size: 0.72rem;
      color: var(--gold);
      font-weight: 500;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    .container {
      max-width: 1080px;
      margin: 0 auto;
      padding: 0 28px;
    }

    .reveal {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.9s ease, transform 0.9s ease;
    }

    .reveal.in {
      opacity: 1;
      transform: translateY(0);
    }

    .vine {
      display: flex;
      justify-content: center;
      margin: 8px 0;
    }

    .vine svg {
      width: 220px;
      height: auto;
    }

    .vine path {
      stroke-dasharray: 400;
      stroke-dashoffset: 400;
      transition: stroke-dashoffset 1.6s ease;
    }

    .vine.in path {
      stroke-dashoffset: 0;
    }

    .vine.in circle,
    .vine.in path[fill] {
      opacity: 0;
      animation: fade-pop 0.5s ease 1.1s forwards;
    }

    @keyframes fade-pop {
      to {
        opacity: 1;
      }
    }

    nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 50;
      display: flex;
      justify-content: center;
      gap: clamp(12px, 3.5vw, 32px);
      padding: 18px 10px;
      background: rgba(248, 244, 234, 0.0);
      transition: background 0.4s ease, box-shadow 0.4s ease, padding 0.4s ease;
      font-size: 0.7rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      flex-wrap: wrap;
    }

    nav.scrolled {
      background: rgba(248, 244, 234, 0.92);
      backdrop-filter: blur(6px);
      box-shadow: 0 2px 20px rgba(56, 64, 46, 0.06);
      padding: 14px 10px;
    }

    nav a {
      color: var(--olive-dark);
      opacity: 0.75;
      transition: opacity 0.2s;
      white-space: nowrap;
    }

    nav a:hover {
      opacity: 1;
    }

    .progress-bar {
      position: fixed;
      top: 0;
      left: 0;
      height: 2px;
      width: 0%;
      z-index: 60;
      background: linear-gradient(90deg, var(--sage-deep), var(--gold));
      transition: width 0.1s linear;
    }

    .toast {
      position: fixed;
      left: 50%;
      bottom: 34px;
      transform: translateX(-50%) translateY(20px);
      background: var(--olive-dark);
      color: var(--ivory);
      padding: 12px 26px;
      border-radius: 30px;
      font-size: 0.85rem;
      letter-spacing: 0.03em;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease, transform 0.3s ease;
      z-index: 80;
    }

    .toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .hero {
      min-height: 100svh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
      padding: 120px 24px 60px;
      background:
        radial-gradient(ellipse at 20% 15%, rgba(139, 154, 123, 0.16), transparent 55%),
        radial-gradient(ellipse at 85% 85%, rgba(185, 154, 91, 0.14), transparent 55%),
        var(--ivory);
    }

    .hero .corner {
      position: absolute;
      width: 120px;
      opacity: 0.55;
    }

    .hero .corner.tl {
      top: 24px;
      left: 24px;
      transform: scaleX(1);
    }

    .hero .corner.br {
      bottom: 24px;
      right: 24px;
      transform: rotate(180deg);
    }

    .monogram {
      width: 96px;
      height: 96px;
      border: 1px solid var(--gold);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 28px;
      font-family: 'Playfair Display', serif;
      font-style: italic;
      font-size: 1.6rem;
      color: var(--sage-deep);
      letter-spacing: 0.05em;
    }

    .hero .eyebrow {
      margin-bottom: 18px;
    }

    .hero h1 {
      font-size: clamp(2.6rem, 7vw, 5rem);
      line-height: 1.05;
      font-style: italic;
      font-weight: 500;
    }

    .hero .amp {
      color: var(--gold);
      display: block;
      font-size: 0.55em;
      margin: 6px 0;
      font-style: normal;
    }

    .hero .date-line {
      margin-top: 30px;
      font-family: 'Jost', sans-serif;
      letter-spacing: 0.3em;
      font-size: 0.95rem;
      color: var(--sage-deep);
      text-transform: uppercase;
    }

    .hero .place-line {
      margin-top: 8px;
      color: var(--ink);
      opacity: 0.7;
      font-size: 0.95rem;
      letter-spacing: 0.05em;
    }

    .countdown {
      display: flex;
      gap: clamp(14px, 4vw, 32px);
      margin-top: 38px;
    }

    .countdown .unit {
      display: flex;
      flex-direction: column;
      align-items: center;
      min-width: 56px;
    }

    .countdown .num {
      font-family: 'Playfair Display', serif;
      font-size: clamp(1.7rem, 4vw, 2.4rem);
      color: var(--sage-deep);
      font-weight: 600;
      line-height: 1;
      font-variant-numeric: tabular-nums;
    }

    .countdown .lbl {
      margin-top: 8px;
      font-size: 0.62rem;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--ink);
      opacity: 0.55;
    }

    .countdown .sep {
      font-size: 1.4rem;
      color: var(--gold-light);
      align-self: flex-start;
      margin-top: 2px;
    }

    .scroll-cue {
      position: absolute;
      bottom: 34px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.68rem;
      letter-spacing: 0.3em;
      text-transform: uppercase;
      color: var(--sage-deep);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      opacity: 0.7;
    }

    .scroll-cue .line {
      width: 1px;
      height: 34px;
      background: linear-gradient(var(--sage-deep), transparent);
      animation: pulse 2.4s ease-in-out infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 0.3;
      }

      50% {
        opacity: 1;
      }
    }

    .petal {
      position: absolute;
      top: -24px;
      width: 11px;
      height: 11px;
      border-radius: 0 60% 0 60%;
      background: var(--sage-soft);
      opacity: 0.55;
      pointer-events: none;
      animation: fall linear infinite;
    }

    @keyframes fall {
      0% {
        transform: translateY(0) rotate(0deg);
        opacity: 0;
      }

      8% {
        opacity: 0.55;
      }

      92% {
        opacity: 0.4;
      }

      100% {
        transform: translateY(105vh) rotate(300deg);
        opacity: 0;
      }
    }

    section {
      padding: 110px 0;
    }

    .section-head {
      text-align: center;
      max-width: 640px;
      margin: 0 auto 60px;
    }

    .section-head h2 {
      font-size: clamp(2rem, 4.4vw, 2.9rem);
      font-style: italic;
      margin-top: 14px;
    }

    .section-head p {
      margin-top: 16px;
      line-height: 1.75;
      opacity: 0.75;
      font-size: 1.02rem;
    }

    .story {
      background: var(--white);
    }

    .story-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 60px;
      align-items: center;
    }

    .story-photo {
      aspect-ratio: 4/5;
      border-radius: 4px;
      overflow: hidden;
      position: relative;
      background: linear-gradient(150deg, var(--sage-soft), var(--ivory-deep));
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      justify-content: center;
      transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
      transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .story-photo:hover {
      box-shadow: 0 26px 60px rgba(56, 64, 46, 0.2);
    }

    .story-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .story-text p {
      line-height: 1.9;
      font-size: 1.05rem;
      color: var(--ink);
      opacity: 0.85;
      margin-bottom: 18px;
    }

    .story-text .quote {
      font-family: 'Playfair Display', serif;
      font-style: italic;
      color: var(--sage-deep);
      font-size: 1.3rem;
      margin-top: 26px;
      line-height: 1.6;
    }

    .details {
      background: var(--ivory-deep);
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 28px;
    }

    .card {
      background: var(--white);
      padding: 44px 32px;
      border-radius: 4px;
      text-align: center;
      box-shadow: var(--shadow);
      position: relative;
      transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg));
      transition: transform 0.35s ease, box-shadow 0.35s ease;
    }

    .card:hover {
      box-shadow: 0 26px 60px rgba(56, 64, 46, 0.2);
    }

    .card .icon {
      width: 44px;
      height: 44px;
      margin: 0 auto 20px;
      color: var(--gold);
    }

    .card h3 {
      font-size: 1.35rem;
      font-style: italic;
      margin-bottom: 12px;
    }

    .card p {
      opacity: 0.75;
      line-height: 1.7;
      font-size: 0.96rem;
    }

    .card .accent-line {
      width: 30px;
      height: 1px;
      background: var(--gold);
      margin: 16px auto;
    }

    /* ESTILOS CÓDIGO DE VESTIMENTA DETALLADO */
    .dresscode-section {
      background: var(--white);
      padding: 80px 0;
    }

    .dresscode-card {
      background: var(--ivory);
      border: 1px solid var(--gold-light);
      border-radius: 8px;
      padding: 40px 28px;
      box-shadow: var(--shadow);
      max-width: 860px;
      margin: 0 auto;
    }

    .dresscode-subhead {
      text-align: center;
      font-size: 0.85rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--sage-deep);
      margin-bottom: 24px;
      font-weight: 500;
    }

    .color-swatches {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 12px;
      margin-top: 16px;
    }

    .swatch {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      box-shadow: 0 2px 6px rgba(0,0,0,0.12);
      position: relative;
    }

    .swatch-forbidden {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      width: 120px;
    }

    .swatch-forbidden .swatch-circle {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 3px 8px rgba(0,0,0,0.15);
      margin-bottom: 10px;
    }

    .swatch-forbidden .swatch-circle::after {
      content: '✕';
      color: #B93838;
      font-size: 1.4rem;
      font-weight: bold;
    }

    .swatch-forbidden span {
      font-size: 0.8rem;
      line-height: 1.3;
      color: var(--ink);
    }

    .forbidden-grid {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 24px;
      margin-top: 20px;
    }

    .forbidden-box {
      background: rgba(201, 139, 122, 0.08);
      border: 1px solid rgba(201, 139, 122, 0.3);
      border-radius: 6px;
      padding: 24px;
      margin-top: 30px;
    }

    .considerations-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 20px;
      margin-top: 30px;
      text-align: center;
    }

    .consideration-item {
      padding: 16px;
      background: var(--white);
      border-radius: 6px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
      font-size: 0.85rem;
      line-height: 1.5;
    }

    .copy-addr {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 10px;
      background: transparent;
      border: 1px solid rgba(56, 64, 46, 0.25);
      color: var(--sage-deep);
      padding: 7px 16px;
      border-radius: 20px;
      font-family: 'Jost', sans-serif;
      font-size: 0.72rem;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
    }

    .copy-addr:hover {
      border-color: var(--gold);
      background: rgba(185, 154, 91, 0.08);
    }

    .copy-addr svg {
      width: 12px;
      height: 12px;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.6;
    }

    .rsvp {
      background: var(--white);
    }

    .rsvp-box {
      max-width: 480px;
      margin: 0 auto;
      text-align: center;
      background: var(--ivory-deep);
      border-radius: 6px;
      padding: 40px 32px;
      box-shadow: var(--shadow);
    }

    .rsvp-box input[type=text] {
      width: 100%;
      background: var(--white);
      border: 1px solid rgba(56, 64, 46, 0.18);
      color: var(--ink);
      padding: 13px 16px;
      border-radius: 4px;
      font-family: 'Jost', sans-serif;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
      margin-bottom: 18px;
    }

    .rsvp-box input[type=text]:focus {
      border-color: var(--gold);
    }

    .whatsapp-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: var(--whatsapp);
      color: var(--white);
      border: none;
      padding: 15px 30px;
      border-radius: 30px;
      font-family: 'Jost', sans-serif;
      font-size: 0.85rem;
      letter-spacing: 0.06em;
      font-weight: 500;
      cursor: pointer;
      transition: filter 0.2s, transform 0.15s;
      width: 100%;
    }

    .whatsapp-btn:hover {
      filter: brightness(1.08);
    }

    .whatsapp-btn:active {
      transform: scale(0.98);
    }

    .whatsapp-btn svg {
      width: 19px;
      height: 19px;
      fill: currentColor;
      flex-shrink: 0;
    }

    .timeline {
      background: var(--white);
    }

    .tl-wrap {
      max-width: 560px;
      margin: 0 auto;
      position: relative;
    }

    .tl-wrap::before {
      content: '';
      position: absolute;
      left: 19px;
      top: 6px;
      bottom: 6px;
      width: 1px;
      background: linear-gradient(var(--gold-light), var(--sage), var(--gold-light));
    }

    .tl-item {
      position: relative;
      padding: 0 0 46px 62px;
    }

    .tl-item:last-child {
      padding-bottom: 0;
    }

    .tl-item::before {
      content: '';
      position: absolute;
      left: 12px;
      top: 2px;
      width: 15px;
      height: 15px;
      border-radius: 50%;
      background: var(--white);
      border: 2px solid var(--gold);
    }

    .tl-item .time {
      font-size: 0.78rem;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--gold);
    }

    .tl-item h3 {
      font-size: 1.2rem;
      margin-top: 6px;
      font-style: italic;
    }

    .tl-item p {
      opacity: 0.7;
      margin-top: 6px;
      font-size: 0.94rem;
      line-height: 1.6;
    }

    .game {
      background: var(--ivory-deep);
    }

    .game-box {
      max-width: 640px;
      margin: 0 auto;
      background: var(--white);
      border-radius: 6px;
      box-shadow: var(--shadow);
      padding: 40px 32px 32px;
    }

    .game-track {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 8px;
    }

    .game-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      border: 1.5px solid var(--gold);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-family: 'Playfair Display', serif;
      font-style: italic;
      font-size: 1.15rem;
      color: var(--sage-deep);
      background: var(--ivory);
      transition: transform 0.4s ease;
    }

    .game-progress-line {
      flex: 1;
      height: 3px;
      background: rgba(139, 154, 123, 0.2);
      border-radius: 3px;
      position: relative;
      overflow: hidden;
    }

    .game-progress-fill {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 0%;
      background: linear-gradient(90deg, var(--sage-deep), var(--gold));
      transition: width 0.35s ease;
    }

    .game-arena {
      position: relative;
      height: 230px;
      margin: 22px 0 16px;
      border-radius: 6px;
      overflow: hidden;
      background: linear-gradient(180deg, rgba(139, 154, 123, 0.06), rgba(217, 196, 149, 0.08));
      border: 1px solid rgba(139, 154, 123, 0.18);
    }

    .game-heart {
      position: absolute;
      top: -26px;
      width: 26px;
      height: 24px;
      cursor: pointer;
      animation: heart-fall linear forwards;
    }

    .game-heart svg {
      width: 100%;
      height: 100%;
      fill: #C98B7A;
      filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.15));
    }

    .game-heart:hover svg {
      fill: #B99A5B;
    }

    @keyframes heart-fall {
      to {
        transform: translateY(260px) rotate(20deg);
      }
    }

    .game-heart.pop {
      animation: none;
      transform: scale(1.6);
      opacity: 0;
      transition: transform 0.25s ease, opacity 0.25s ease;
    }

    .game-hud {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }

    .game-score {
      font-size: 0.85rem;
      color: var(--sage-deep);
      letter-spacing: 0.04em;
    }

    .game-btn {
      width: auto;
      padding: 12px 28px;
      background: var(--gold);
      color: var(--olive-dark);
    }

    .game-btn:hover {
      background: var(--gold-light);
    }

    .game-win {
      position: absolute;
      inset: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      background: rgba(248, 244, 234, 0.9);
      text-align: center;
      padding: 20px;
    }

    .game-win-text {
      font-family: 'Playfair Display', serif;
      font-style: italic;
      font-size: 1.5rem;
      color: var(--sage-deep);
      margin-bottom: 16px;
    }

    .gallery {
      background: var(--olive-dark);
      color: var(--ivory);
    }

    .gallery .eyebrow {
      color: var(--gold-light);
    }

    .gallery .section-head h2 {
      color: var(--ivory);
    }

    .gallery .section-head p {
      color: var(--ivory);
      opacity: 0.75;
    }

    .gallery-notice {
      max-width: 560px;
      margin: 0 auto 40px;
      text-align: center;
      font-size: 0.8rem;
      color: var(--gold-light);
      opacity: 0.75;
      line-height: 1.6;
      border-top: 1px solid rgba(217, 196, 149, 0.25);
      border-bottom: 1px solid rgba(217, 196, 149, 0.25);
      padding: 14px 10px;
    }

    .banner {
      max-width: 560px;
      margin: 0 auto 30px;
      text-align: center;
      padding: 14px 18px;
      border-radius: 6px;
      font-size: 0.9rem;
    }

    .banner.ok {
      background: rgba(139, 154, 123, 0.18);
      color: #D9C495;
      border: 1px solid rgba(139, 154, 123, 0.4);
    }

    .banner.err {
      background: rgba(201, 139, 122, 0.18);
      color: #E3B7AC;
      border: 1px solid rgba(201, 139, 122, 0.4);
    }

    .upload-box {
      max-width: 640px;
      margin: 0 auto 56px;
      background: rgba(248, 244, 234, 0.06);
      border: 1px solid rgba(217, 196, 149, 0.3);
      border-radius: 6px;
      padding: 36px;
    }

    .field-label {
      display: block;
      font-size: 0.72rem;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: var(--gold-light);
      opacity: 0.85;
      margin-bottom: 8px;
    }

    .upload-step {
      margin-bottom: 20px;
    }

    input[type=text],
    textarea {
      width: 100%;
      background: rgba(248, 244, 234, 0.08);
      border: 1px solid rgba(217, 196, 149, 0.35);
      color: var(--ivory);
      padding: 13px 16px;
      border-radius: 4px;
      font-family: 'Jost', sans-serif;
      font-size: 0.95rem;
      outline: none;
      transition: border-color 0.2s;
    }

    input[type=text]::placeholder,
    textarea::placeholder {
      color: rgba(248, 244, 234, 0.45);
    }

    input[type=text]:focus,
    textarea:focus {
      border-color: var(--gold);
    }

    textarea {
      resize: vertical;
      min-height: 56px;
    }

    .file-label {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      border: 1.5px dashed rgba(217, 196, 149, 0.5);
      border-radius: 6px;
      padding: 38px 20px;
      cursor: pointer;
      margin-bottom: 10px;
      transition: border-color 0.2s, background 0.2s;
    }

    .file-label:hover {
      border-color: var(--gold);
      background: rgba(217, 196, 149, 0.06);
    }

    .file-label input {
      position: absolute;
      width: 1px;
      height: 1px;
      opacity: 0;
      overflow: hidden;
    }

    .fl-icon {
      width: 38px;
      height: 38px;
      color: var(--gold-light);
      margin-bottom: 14px;
      opacity: 0.9;
    }

    .fl-icon svg {
      width: 100%;
      height: 100%;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.3;
    }

    .fl-text {
      color: var(--ivory);
      font-size: 0.95rem;
      line-height: 1.5;
    }

    .fl-text span {
      display: block;
      font-size: 0.78rem;
      opacity: 0.55;
      margin-top: 4px;
    }

    .file-picked {
      text-align: center;
      font-size: 0.82rem;
      color: var(--gold-light);
      margin-bottom: 18px;
      min-height: 18px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      background: var(--gold);
      color: var(--olive-dark);
      border: none;
      padding: 14px 34px;
      border-radius: 2px;
      font-family: 'Jost', sans-serif;
      font-size: 0.85rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      width: 100%;
    }

    .btn:hover {
      background: var(--gold-light);
    }

    .btn:active {
      transform: scale(0.98);
    }

    .gallery-controls {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
      margin-bottom: 34px;
      flex-wrap: wrap;
    }

    .download-all,
    .sort-link {
      background: transparent;
      border: 1px solid rgba(217, 196, 149, 0.4);
      color: var(--ivory);
      padding: 9px 20px;
      border-radius: 20px;
      font-family: 'Jost', sans-serif;
      font-size: 0.75rem;
      letter-spacing: 0.1em;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 7px;
      transition: opacity 0.2s, border-color 0.2s, background 0.2s;
      opacity: 0.7;
    }

    .download-all {
      color: var(--gold-light);
    }

    .download-all:hover,
    .sort-link:hover {
      opacity: 0.95;
      border-color: var(--gold);
      background: rgba(217, 196, 149, 0.1);
    }

    .download-all svg {
      width: 13px;
      height: 13px;
      fill: none;
      stroke: currentColor;
      stroke-width: 1.6;
    }

    .sort-link.active {
      opacity: 1;
      border-color: var(--gold);
      background: rgba(217, 196, 149, 0.1);
    }

    .masonry {
      columns: 3 220px;
      column-gap: 16px;
    }

    .masonry .item {
      break-inside: avoid;
      margin-bottom: 16px;
      border-radius: 4px;
      overflow: hidden;
      position: relative;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
      cursor: pointer;
      opacity: 0;
      transform: translateY(14px) scale(0.98);
      animation: pop-in 0.6s ease forwards;
    }

    @keyframes pop-in {
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .masonry img {
      width: 100%;
      display: block;
      transition: transform 0.4s ease;
    }

    .masonry .item:hover img {
      transform: scale(1.04);
    }

    .masonry .cap {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      padding: 10px 12px;
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
      font-size: 0.78rem;
      color: var(--ivory);
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 8px;
    }

    .masonry .cap b {
      color: var(--gold-light);
      font-weight: 500;
    }

    .masonry .cap-text {
      flex: 1;
      min-width: 0;
    }

    .like-form {
      margin: 0;
      flex-shrink: 0;
    }

    .like-btn {
      background: none;
      border: none;
      color: var(--ivory);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 0.78rem;
      padding: 2px;
      opacity: 0.85;
      transition: transform 0.15s;
    }

    .like-btn:hover {
      transform: scale(1.15);
    }

    .like-btn svg {
      width: 15px;
      height: 15px;
      fill: none;
      stroke: var(--ivory);
      stroke-width: 1.6;
    }

    .gallery-empty {
      text-align: center;
      opacity: 0.55;
      font-style: italic;
      font-family: 'Playfair Display', serif;
      padding: 30px 0;
      font-size: 1.05rem;
    }

    .lightbox {
      position: fixed;
      inset: 0;
      background: rgba(20, 22, 15, 0.94);
      z-index: 100;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 40px;
      flex-direction: column;
    }

    .lightbox.open {
      display: flex;
    }

    .lightbox img {
      max-width: 100%;
      max-height: 78vh;
      border-radius: 4px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .lightbox .lb-cap {
      color: var(--ivory);
      margin-top: 16px;
      font-size: 0.9rem;
      opacity: 0.85;
      text-align: center;
    }

    .lightbox .lb-close {
      position: absolute;
      top: 28px;
      right: 32px;
      color: var(--ivory);
      font-size: 1.6rem;
      cursor: pointer;
      opacity: 0.7;
      transition: opacity 0.2s;
    }

    .lightbox .lb-close:hover {
      opacity: 1;
    }

    .lb-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      color: var(--ivory);
      font-size: 2rem;
      cursor: pointer;
      opacity: 0.6;
      transition: opacity 0.2s;
      padding: 10px;
      user-select: none;
    }

    .lb-nav:hover {
      opacity: 1;
    }

    .lb-prev {
      left: 16px;
    }

    .lb-next {
      right: 16px;
    }

    footer {
      background: var(--ivory);
      text-align: center;
      padding: 80px 24px 60px;
    }

    footer .script {
      font-size: 1.6rem;
      font-style: italic;
    }

    footer .small {
      margin-top: 14px;
      opacity: 0.6;
      font-size: 0.85rem;
      letter-spacing: 0.05em;
    }

    @media(max-width:760px) {
      .story-grid {
        grid-template-columns: 1fr;
        gap: 36px;
      }

      .story-photo {
        order: -1;
      }

      .masonry {
        columns: 2 160px;
      }

      nav {
        gap: 10px;
        font-size: 0.6rem;
      }

      section {
        padding: 80px 0;
      }
    }
  </style>
</head>

<body>

  <div class="progress-bar" id="progressBar"></div>
  <div class="toast" id="toast"></div>

  <nav id="nav">
    <a href="#historia">Historia</a>
    <a href="#detalles">Detalles</a>
    <a href="#vestimenta">Vestimenta</a>
    <a href="#confirmar">Confirmar</a>
    <a href="#itinerario">Itinerario</a>
    <a href="#juego">Juego</a>
    <a href="#galeria">Galería</a>
  </nav>

  <!-- HERO -->
  <section class="hero">
    <svg class="corner tl" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M4 4 C 30 6, 40 20, 42 40 C 44 60, 55 66, 70 64" stroke="#8B9A7B" stroke-width="1.2" />
      <circle cx="18" cy="9" r="2.4" fill="#B99A5B" />
      <path d="M20 20 q10 -10 18 -2 q-10 6 -18 2 z" fill="#A6B394" />
      <path d="M34 38 q10 -8 17 0 q-9 7 -17 0 z" fill="#8B9A7B" />
    </svg>
    <svg class="corner br" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M4 4 C 30 6, 40 20, 42 40 C 44 60, 55 66, 70 64" stroke="#8B9A7B" stroke-width="1.2" />
      <circle cx="18" cy="9" r="2.4" fill="#B99A5B" />
      <path d="M20 20 q10 -10 18 -2 q-10 6 -18 2 z" fill="#A6B394" />
      <path d="M34 38 q10 -8 17 0 q-9 7 -17 0 z" fill="#8B9A7B" />
    </svg>

    <div id="petals"></div>

    <div class="monogram">X&nbsp;&amp;&nbsp;R</div>
    <div class="eyebrow">Nos casamos</div>
    <h1>Xitlally<span class="amp">&amp;</span>Robert</h1>
    <div class="date-line">Martes · 29 de Diciembre de 2026</div>
    <div class="place-line">El Caracol — Ceremonia Civil &amp; Fiesta</div>

    <div class="countdown" id="countdown">
      <div class="unit"><span class="num" id="cd-days">00</span><span class="lbl">Días</span></div>
      <span class="sep">·</span>
      <div class="unit"><span class="num" id="cd-hours">00</span><span class="lbl">Horas</span></div>
      <span class="sep">·</span>
      <div class="unit"><span class="num" id="cd-min">00</span><span class="lbl">Min</span></div>
      <span class="sep">·</span>
      <div class="unit"><span class="num" id="cd-sec">00</span><span class="lbl">Seg</span></div>
    </div>

    <div class="scroll-cue"><span>Desliza</span><span class="line"></span></div>
  </section>

  <!-- HISTORIA -->
  <section class="story" id="historia">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">Cómo empezó</div>
        <h2>Nuestra Historia</h2>
      </div>
      <div class="story-grid">
        <div class="story-text reveal">
          <p>
            Todo comenzó el 3 de septiembre de 2019, marcando el inicio de una historia que transformaría nuestras
            vidas.

            Juntos vivimos la universidad, las graduaciones y las preocupaciones cotidianas, descubriendo que celebrar
            cada logro en pareja lo hace aún más especial. Compartimos aventuras inolvidables —desde acampar frente al
            mar hasta recorrer en motocicleta el camino a la Isla de la Piedra—, pero también momentos sencillos en casa
            donde aprendimos que la felicidad está en los detalles cotidianos.

            No todo fue fácil. Atravesamos días difíciles que nos enseñaron a escucharnos, comprendernos y ser refugio
            el uno para el otro.

            A finales de 2024, en el mismo restaurante donde la vi por primera vez, le hice la pregunta que definiría
            nuestro futuro: ¿Quieres casarte conmigo? Su respuesta abrió la puerta al sueño de formar una familia.
          </p>
          <p>
            Hoy, 29 de diciembre de 2026, tras años de risas, viajes y obstáculos superados, no terminamos esta
            historia: comenzamos nuestro capítulo más importante. No sabemos qué nos depara el camino, pero tenemos la
            certeza de con quién queremos recorrerlo.
          </p>
          <p class="quote">"Tú y yo. Hoy, mañana y siempre."</p>
        </div>
        <div class="story-photo reveal">
          <!-- Foto de la pareja cargada directamente -->
          <img src="novios.jpeg" alt="Xitlally y Robert">
        </div>
      </div>
    </div>
  </section>

  <div class="vine reveal">
    <svg viewBox="0 0 220 40" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M4 20 C 60 4, 100 36, 160 20 S 210 4, 216 20" stroke="#B99A5B" stroke-width="1" />
      <circle cx="110" cy="21" r="3" fill="#B99A5B" />
      <path d="M40 20 q8 -9 16 -1 q-8 6 -16 1z" fill="#8B9A7B" />
      <path d="M80 22 q8 8 16 1 q-8 -7 -16 -1z" fill="#A6B394" />
      <path d="M140 19 q8 -9 16 -1 q-8 6 -16 1z" fill="#8B9A7B" />
      <path d="M178 22 q8 8 16 1 q-8 -7 -16 -1z" fill="#A6B394" />
    </svg>
  </div>

  <!-- DETALLES -->
  <section class="details" id="detalles">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">Información</div>
        <h2>Los Detalles</h2>
        <p>Todo lo que necesitas saber para acompañarnos ese día.</p>
      </div>
      <div class="cards">
        <div class="card reveal">
          <svg class="icon" viewBox="0 0 48 48" fill="none">
            <path d="M24 4 L24 14 M14 8 L34 8 M12 16 h24 l-2 26 h-20 z" stroke="currentColor" stroke-width="1.4" />
          </svg>
          <h3>Ceremonia Civil</h3>
          <div class="accent-line"></div>
          <p>El Caracol<br>Hora por confirmar</p>
          <button class="copy-addr" data-copy="'El Caracol', Veracruz 61, Centro, 82400 Escuinapa de Hidalgo, Sin."
            type="button">
            <svg viewBox="0 0 24 24">
              <rect x="9" y="9" width="12" height="12" rx="1.5" />
              <path d="M5 15V5a2 2 0 0 1 2-2h10" />
            </svg>
            Copiar dirección
          </button>
        </div>
        <div class="card reveal">
          <svg class="icon" viewBox="0 0 48 48" fill="none">
            <path d="M8 40 h32 M12 40 V22 a12 12 0 0 1 24 0 v18" stroke="currentColor" stroke-width="1.4" />
          </svg>
          <h3>Fiesta</h3>
          <div class="accent-line"></div>
          <p>Recepción y celebración<br>El Caracol<br>Hora por confirmar</p>
        </div>
        <div class="card reveal">
          <svg class="icon" viewBox="0 0 48 48" fill="none">
            <path d="M18 6 h12 l4 8 -10 30 -10 -30 z" stroke="currentColor" stroke-width="1.4" />
          </svg>
          <h3>Código de Vestimenta</h3>
          <div class="accent-line"></div>
          <p>Formal / Etiqueta<br>Revisa los colores permitidos y no permitidos más abajo.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CÓDIGO DE VESTIMENTA -->
  <section class="dresscode-section" id="vestimenta">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">Cómo vestir</div>
        <h2>Código de Vestimenta</h2>
        <p>Para mantener la armonía y la estética de nuestra celebración, te compartimos algunas indicaciones sobre la vestimenta.</p>
      </div>

      <div class="dresscode-card reveal">
        <!-- COLORES PERMITIDOS -->
        <div class="dresscode-subhead">✦ Colores Permitidos ✦</div>
        <p style="text-align: center; font-size: 0.9rem; opacity: 0.8; margin-bottom: 16px;">
          Puedes usar cualquier color, excepto los indicados en la sección de "colores no permitidos".
        </p>
        <div class="color-swatches">
          <div class="swatch" style="background: #F4C7CF;" title="Rosa pastel"></div>
          <div class="swatch" style="background: #A46B78;" title="Rosa viejo"></div>
          <div class="swatch" style="background: #815C77;" title="Uva"></div>
          <div class="swatch" style="background: #9D88B3;" title="Lila"></div>
          <div class="swatch" style="background: #4B5272;" title="Azul índigo"></div>
          <div class="swatch" style="background: #1B2C42;" title="Azul marino"></div>
          <div class="swatch" style="background: #5C7F99;" title="Azul acero"></div>
          <div class="swatch" style="background: #9BB098;" title="Verde salvia"></div>
          <div class="swatch" style="background: #5E6D4E;" title="Verde oliva"></div>
          <div class="swatch" style="background: #253D2E;" title="Verde bosque"></div>
          <!-- <div class="swatch" style="background: #AAB5C4;" title="Gris azulado"></div> -->
          <div class="swatch" style="background: #3B4653;" title="Madrás"></div>
          <div class="swatch" style="background: #232323;" title="Negro"></div>
          <!-- <div class="swatch" style="background: #CDC9C3;" title="Gris claro"></div> -->
          <div class="swatch" style="background: #858280;" title="Gris medio"></div>
          <div class="swatch" style="background: #525050;" title="Gris oscuro"></div>
          <!-- <div class="swatch" style="background: #E3D9C6;" title="Arena"></div> -->
          <!-- <div class="swatch" style="background: #B2A88A;" title="Beige"></div> -->
          <div class="swatch" style="background: #C4A493;" title="Nude"></div>
          <div class="swatch" style="background: #A87E88;" title="Palo de rosa"></div>
        </div>

        <!-- COLORES NO PERMITIDOS -->
        <div class="forbidden-box">
          <div class="dresscode-subhead" style="color: #9E3A3A;">✦ Colores No Permitidos ✦</div>
          <div class="forbidden-grid">
            <div class="swatch-forbidden">
              <div class="swatch-circle" style="background: #FFFFFF; border: 1px solid #ddd;"></div>
              <span><b>Blanco</b><br><small>(reservado para la novia)</small></span>
            </div>
            <div class="swatch-forbidden">
              <div class="swatch-circle" style="background: #B92525;"></div>
              <span><b>Rojo</b></span>
            </div>
            <div class="swatch-forbidden">
              <div class="swatch-circle" style="background: #5A3525;"></div>
              <span><b>Café / Chocolate</b><br><small>(reservado para mis damas)</small></span>
            </div>
            <div class="swatch-forbidden">
              <div class="swatch-circle" style="background: linear-gradient(135deg, #FF007F, #76FF03, #FF6D00);"></div>
              <span><b>Colores fosforescentes</b><br><small>o muy llamativos</small></span>
            </div>
          </div>
        </div>

        <!-- CONSIDERACIONES -->
        <div style="margin-top: 40px;">
          <div class="dresscode-subhead">✦ Consideraciones ✦</div>
          <div class="considerations-grid">
            <div class="consideration-item">
              Evita prendas con lentejuelas, brillos excesivos o totalmente cubiertas de lentejuelas.
            </div>
            <div class="consideration-item">
              Se permiten telas lisas, estampados discretos y detalles sencillos.
            </div>
            <div class="consideration-item">
              Puedes usar cualquier otro color o combinación de colores que no esté en la lista de no permitidos.
            </div>
            <div class="consideration-item">
              Gracias por ayudarnos a mantener la estética de nuestro día especial.
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- CONFIRMAR ASISTENCIA -->
  <section class="rsvp" id="confirmar">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">Tu lugar nos importa</div>
        <h2>Confirma tu Asistencia</h2>
        <p>Avísanos si nos acompañarás — nos ayuda muchísimo a organizar todo con tiempo.</p>
      </div>
      <div class="rsvp-box reveal">
        <label class="field-label" for="rsvpName" style="color:var(--sage-deep);">Tu nombre</label>
        <input type="text" id="rsvpName" placeholder="¿Cómo te llamas?" maxlength="60">
        <a href="https://wa.me/521234567890?text=Hola!%20Confirmo%20que%20asistir%C3%A9%20a%20la%20boda%20de%20Xitlally%20y%20Robert%20el%2029%20de%20diciembre%20%F0%9F%8E%89"
          id="rsvpWhatsapp" class="btn whatsapp-btn" target="_blank" rel="noopener">
          <svg viewBox="0 0 32 32">
            <path
              d="M16.001 3C9.373 3 4 8.373 4 15c0 2.386.696 4.611 1.902 6.487L4 29l7.7-1.878A11.94 11.94 0 0 0 16.001 27C22.629 27 28 21.627 28 15S22.629 3 16.001 3zm6.995 16.87c-.297.836-1.47 1.556-2.412 1.756-.642.135-1.48.243-4.303-.923-3.61-1.492-5.933-5.14-6.114-5.377-.174-.237-1.463-1.949-1.463-3.716s.912-2.637 1.235-3c.323-.362.706-.453.94-.453.235 0 .47.002.675.012.216.01.505-.082.79.603.297.703 1.01 2.427 1.098 2.603.089.176.148.383.03.62-.118.235-.177.383-.353.588-.176.206-.37.46-.529.618-.176.176-.36.367-.155.72.206.353.914 1.51 1.963 2.446 1.348 1.204 2.484 1.578 2.838 1.755.353.176.559.147.765-.088.206-.235.883-1.03 1.118-1.383.235-.353.47-.294.79-.176.323.118 2.043.964 2.394 1.14.353.176.588.264.675.412.088.147.088.85-.208 1.687z" />
          </svg>
          Confirmar por WhatsApp
        </a>
      </div>
    </div>
  </section>

  <!-- ITINERARIO -->
  <section class="timeline" id="itinerario">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">El gran día</div>
        <h2>Itinerario</h2>
      </div>
      <div class="tl-wrap">
        <div class="tl-item reveal">
          <div class="time">3:00 - 3:30 pm</div>
          <h3>Bienvenida de invitados</h3>
          <p>Llegada a El Caracol.</p>
        </div>
        <div class="tl-item reveal">
          <div class="time">3:30 - 5:00 pm </div>
          <h3>Ceremonia Civil</h3>
          <p>Firma del acta ante el juez civil.</p>
        </div>
        <div class="tl-item reveal">
          <div class="time">5:00 - 6:00 pm</div>
          <h3>Cóctel de bienvenida</h3>
          <p>Bebidas y aperitivos mientras se preparan las fotografías.</p>
        </div>
        <div class="tl-item reveal">
          <div class="time">6:00 pm en Adelante</div>
          <h3>Cena y brindis</h3>
          <p>Cena y palabras de los padrinos.</p>
        </div>
        <div class="tl-item reveal">
          <div class="time">6:00 pm en Adelante</div>
          <h3>Baile y fiesta</h3>
          <p>Primer baile de los novios y fiesta hasta la madrugada.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- JUEGO -->
  <section class="game" id="juego">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">Mientras esperamos</div>
        <h2>Reúne a los Novios</h2>
        <p>Atrapa los corazones antes de que caigan y ayuda a Xitlally y Robert a encontrarse.</p>
      </div>
      <div class="game-box reveal">
        <div class="game-track">
          <div class="game-avatar" id="avatarX">X</div>
          <div class="game-progress-line">
            <div class="game-progress-fill" id="gameProgressFill"></div>
          </div>
          <div class="game-avatar" id="avatarR">R</div>
        </div>
        <div class="game-arena" id="gameArena">
          <div class="game-win" id="gameWin" style="display:none;">
            <div class="game-win-text">¡Se encontraron! 💍</div>
            <button class="btn game-btn" id="gameReplayBtn" type="button">Jugar de nuevo</button>
          </div>
        </div>
        <div class="game-hud">
          <span class="game-score" id="gameScore">0 / 15 corazones</span>
          <button class="btn game-btn" id="gameStartBtn" type="button">Jugar</button>
        </div>
      </div>
    </div>
  </section>

  <!-- GALERIA -->
  <section class="gallery" id="galeria">
    <div class="container">
      <div class="section-head reveal">
        <div class="eyebrow">Momentos compartidos</div>
        <h2>Comparte tus Fotos</h2>
        <p>¿Capturaste un buen momento durante la boda? Súbelo aquí para que todos podamos revivirlo juntos.</p>
      </div>

      <div class="gallery-notice reveal">
        Las fotos quedan guardadas aquí temporalmente. Xitlally y Robert las descargarán todas después del evento, así
        que no te preocupes por guardarlas tú — solo comparte el momento 🌿
      </div>

      <?php if ($uploadedCount !== null): ?>
        <?php if ($uploadedCount > 0): ?>
          <div class="banner ok reveal">¡Gracias! Se subieron <?= $uploadedCount ?> foto<?= $uploadedCount > 1 ? 's' : '' ?>
            correctamente.</div>
        <?php else: ?>
          <div class="banner err reveal">No se pudo subir ninguna foto. Revisa que sean JPG, PNG o WEBP y pesen menos de
            8MB.</div>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($uploadError): ?>
        <div class="banner err reveal"><?= htmlspecialchars($uploadError) ?></div>
      <?php endif; ?>

      <form class="upload-box reveal" action="api/upload.php" method="post" enctype="multipart/form-data">
        <div class="upload-step">
          <label class="field-label" for="guestName">Tu nombre</label>
          <input type="text" id="guestName" name="name" placeholder="¿Cómo te llamas?" maxlength="40">
        </div>

        <label class="file-label" for="fileInput">
          <input type="file" id="fileInput" name="photos[]" accept="image/*" multiple required>
          <div class="fl-icon">
            <svg viewBox="0 0 48 48">
              <path d="M24 6 v24 M14 16 l10 -10 10 10 M8 32 v6 a4 4 0 0 0 4 4 h24 a4 4 0 0 0 4 -4 v-6" />
            </svg>
          </div>
          <div class="fl-text">Toca para elegir tus fotos<br><span>puedes seleccionar varias a la vez</span></div>
        </label>
        <div class="file-picked" id="filePicked"></div>

        <div class="upload-step">
          <label class="field-label" for="caption">Mensaje para los novios (opcional)</label>
          <textarea id="caption" name="caption" placeholder="Deja unas palabras..." maxlength="140"></textarea>
        </div>

        <button class="btn" type="submit">Subir fotos</button>
      </form>

      <div class="gallery-controls reveal">
        <a class="sort-link <?= $sort === 'recent' ? 'active' : '' ?>" href="?sort=recent#galeria">Más recientes</a>
        <a class="sort-link <?= $sort === 'liked' ? 'active' : '' ?>" href="?sort=liked#galeria">Más queridas</a>
        <a class="download-all" href="api/download-all.php">
          <svg viewBox="0 0 24 24">
            <path d="M12 3v13m0 0-5-5m5 5 5-5M5 21h14" />
          </svg>
          Descargar todas
        </a>
      </div>

      <?php if (empty($photos)): ?>
        <div class="gallery-empty">Aún no hay fotos — ¡sé el primero en compartir un momento!</div>
      <?php else: ?>
        <div class="masonry" id="masonry">
          <?php foreach ($photos as $idx => $p): ?>
            <div class="item" style="animation-delay:<?= $idx * 0.05 ?>s;" data-index="<?= $idx ?>">
              <img src="<?= htmlspecialchars(UPLOAD_URL . $p['filename']) ?>" loading="lazy"
                alt="Foto de <?= htmlspecialchars($p['name']) ?>">
              <div class="cap">
                <div class="cap-text">
                  <b><?= htmlspecialchars($p['name']) ?></b><?= $p['caption'] ? ' — ' . htmlspecialchars($p['caption']) : '' ?>
                </div>
                <form class="like-form" action="api/like.php" method="post">
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                  <button class="like-btn" type="submit" aria-label="Me gusta">
                    <svg viewBox="0 0 24 24">
                      <path
                        d="M12 21s-7.5-4.6-10-9.2C.5 8.2 2.6 4.8 6.2 4.5c2-.2 3.7 1 4.8 2.6 1.1-1.6 2.8-2.8 4.8-2.6 3.6.3 5.7 3.7 4.2 7.3-2.5 4.6-10 9.2-10 9.2z" />
                    </svg>
                    <span><?= (int) $p['likes'] ?></span>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- LIGHTBOX -->
  <div class="lightbox" id="lightbox">
    <span class="lb-close" id="lbClose">&times;</span>
    <span class="lb-nav lb-prev" id="lbPrev">&#8249;</span>
    <img id="lbImg" src="">
    <span class="lb-nav lb-next" id="lbNext">&#8250;</span>
    <div class="lb-cap" id="lbCap"></div>
  </div>

  <footer>
    <div class="vine reveal"><svg viewBox="0 0 220 40" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M4 20 C 60 4, 100 36, 160 20 S 210 4, 216 20" stroke="#B99A5B" stroke-width="1" />
        <circle cx="110" cy="21" r="3" fill="#B99A5B" />
        <path d="M40 20 q8 -9 16 -1 q-8 6 -16 1z" fill="#8B9A7B" />
        <path d="M178 22 q8 8 16 1 q-8 -7 -16 -1z" fill="#A6B394" />
      </svg></div>
    <div class="script reveal">Gracias por ser parte de nuestra historia</div>
    <div class="small reveal">Xitlally &amp; Robert · 29 de Diciembre de 2026</div>
  </footer>

  <script>
    const photos = <?php echo json_encode($photosForJs, JSON_UNESCAPED_UNICODE); ?>;

    const revealEls = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver((entries) => {
      entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } });
    }, { threshold: 0.15 });
    revealEls.forEach(el => io.observe(el));

    const nav = document.getElementById('nav');
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 40);
    });

    const progressBar = document.getElementById('progressBar');
    window.addEventListener('scroll', () => {
      const h = document.documentElement;
      const pct = (h.scrollTop) / (h.scrollHeight - h.clientHeight) * 100;
      progressBar.style.width = pct + '%';
    });

    document.querySelectorAll('.card, .story-photo').forEach(el => {
      el.addEventListener('mousemove', (e) => {
        const rect = el.getBoundingClientRect();
        const x = (e.clientX - rect.left) / rect.width - 0.5;
        const y = (e.clientY - rect.top) / rect.height - 0.5;
        el.style.setProperty('--ry', (x * 8) + 'deg');
        el.style.setProperty('--rx', (-y * 8) + 'deg');
      });
      el.addEventListener('mouseleave', () => {
        el.style.setProperty('--ry', '0deg');
        el.style.setProperty('--rx', '0deg');
      });
    });

    const toast = document.getElementById('toast');
    let toastTimer = null;
    function showToast(msg) {
      toast.textContent = msg;
      toast.classList.add('show');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => toast.classList.remove('show'), 2400);
    }

    document.querySelectorAll('.copy-addr').forEach(btn => {
      btn.addEventListener('click', async () => {
        const text = btn.dataset.copy;
        try {
          await navigator.clipboard.writeText(text);
          showToast('Dirección copiada 🌿');
        } catch (e) {
          showToast('No se pudo copiar. Cópiala manualmente.');
        }
      });
    });

    const COUPLE_WHATSAPP = '6951134604';
    const rsvpName = document.getElementById('rsvpName');
    const rsvpBtn = document.getElementById('rsvpWhatsapp');
    rsvpBtn.addEventListener('click', () => {
      const name = rsvpName.value.trim();
      const msg = name
        ? `Hola! Soy ${name} y confirmo que asistiré a la boda de Xitlally y Robert el 29 de diciembre 🎉`
        : 'Hola! Confirmo que asistiré a la boda de Xitlally y Robert el 29 de diciembre 🎉';
      rsvpBtn.href = `https://wa.me/${COUPLE_WHATSAPP}?text=${encodeURIComponent(msg)}`;
    });

    const gameArena = document.getElementById('gameArena');
    const gameProgressFill = document.getElementById('gameProgressFill');
    const gameScore = document.getElementById('gameScore');
    const gameStartBtn = document.getElementById('gameStartBtn');
    const gameReplayBtn = document.getElementById('gameReplayBtn');
    const gameWin = document.getElementById('gameWin');
    const avatarX = document.getElementById('avatarX');
    const avatarR = document.getElementById('avatarR');
    const GAME_TARGET = 15;
    let gameCaught = 0;
    let gameSpawnTimer = null;
    let gameActive = false;
    const heartSvg = '<svg viewBox="0 0 24 24"><path d="M12 21s-7.5-4.6-10-9.2C.5 8.2 2.6 4.8 6.2 4.5c2-.2 3.7 1 4.8 2.6 1.1-1.6 2.8-2.8 4.8-2.6 3.6.3 5.7 3.7 4.2 7.3-2.5 4.6-10 9.2-10 9.2z"/></svg>';

    function spawnHeart() {
      if (!gameActive) return;
      const arenaW = gameArena.clientWidth;
      const heart = document.createElement('div');
      heart.className = 'game-heart';
      heart.style.left = (Math.random() * (arenaW - 30)) + 'px';
      const fallDuration = 3.2 + Math.random() * 1.4;
      heart.style.animationDuration = fallDuration + 's';
      heart.innerHTML = heartSvg;
      heart.addEventListener('click', () => catchHeart(heart));
      heart.addEventListener('animationend', () => {
        if (heart.parentNode) heart.remove();
      });
      gameArena.appendChild(heart);
    }

    function catchHeart(heart) {
      if (!gameActive || heart.classList.contains('pop')) return;
      heart.classList.add('pop');
      setTimeout(() => heart.remove(), 260);
      gameCaught = Math.min(GAME_TARGET, gameCaught + 1);
      updateGameProgress();
      if (gameCaught >= GAME_TARGET) winGame();
    }

    function updateGameProgress() {
      const pct = (gameCaught / GAME_TARGET) * 100;
      gameProgressFill.style.width = pct + '%';
      gameScore.textContent = `${gameCaught} / ${GAME_TARGET} corazones`;
      const closeness = (gameCaught / GAME_TARGET) * 14;
      avatarX.style.transform = `translateX(${closeness}px)`;
      avatarR.style.transform = `translateX(${-closeness}px)`;
    }

    function startGame() {
      gameActive = true;
      gameCaught = 0;
      gameArena.querySelectorAll('.game-heart').forEach(h => h.remove());
      gameWin.style.display = 'none';
      updateGameProgress();
      gameStartBtn.textContent = 'Jugando...';
      gameStartBtn.disabled = true;
      clearInterval(gameSpawnTimer);
      gameSpawnTimer = setInterval(spawnHeart, 650);
    }

    function winGame() {
      gameActive = false;
      clearInterval(gameSpawnTimer);
      gameArena.querySelectorAll('.game-heart').forEach(h => h.remove());
      gameWin.style.display = 'flex';
      gameStartBtn.disabled = false;
      gameStartBtn.textContent = 'Jugar';
      showToast('¡Xitlally y Robert se encontraron! 🌿');
    }

    gameStartBtn.addEventListener('click', startGame);
    gameReplayBtn.addEventListener('click', startGame);

    const weddingDate = new Date('2026-12-29T12:00:00');
    function updateCountdown() {
      const now = new Date();
      let diff = weddingDate - now;
      if (diff < 0) diff = 0;
      const days = Math.floor(diff / 86400000);
      const hours = Math.floor((diff % 86400000) / 3600000);
      const mins = Math.floor((diff % 3600000) / 60000);
      const secs = Math.floor((diff % 60000) / 1000);
      document.getElementById('cd-days').textContent = String(days).padStart(2, '0');
      document.getElementById('cd-hours').textContent = String(hours).padStart(2, '0');
      document.getElementById('cd-min').textContent = String(mins).padStart(2, '0');
      document.getElementById('cd-sec').textContent = String(secs).padStart(2, '0');
    }
    updateCountdown();
    setInterval(updateCountdown, 1000);

    const petalsWrap = document.getElementById('petals');
    const petalColors = ['#8B9A7B', '#A6B394', '#D9C495'];
    for (let i = 0; i < 14; i++) {
      const p = document.createElement('div');
      p.className = 'petal';
      p.style.left = Math.random() * 100 + '%';
      p.style.background = petalColors[i % petalColors.length];
      p.style.animationDuration = (10 + Math.random() * 10) + 's';
      p.style.animationDelay = (Math.random() * 12) + 's';
      p.style.width = p.style.height = (7 + Math.random() * 7) + 'px';
      petalsWrap.appendChild(p);
    }

    const fileInput = document.getElementById('fileInput');
    const filePicked = document.getElementById('filePicked');
    fileInput.addEventListener('change', () => {
      const n = fileInput.files.length;
      filePicked.textContent = n ? `${n} foto${n > 1 ? 's' : ''} seleccionada${n > 1 ? 's' : ''}` : '';
    });

    const lightbox = document.getElementById('lightbox');
    const lbImg = document.getElementById('lbImg');
    const lbCap = document.getElementById('lbCap');
    let currentIndex = 0;

    function escapeHtml(str) {
      const d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }
    function renderLightbox() {
      const item = photos[currentIndex];
      if (!item) return;
      lbImg.src = item.url;
      lbCap.innerHTML = '<b>' + escapeHtml(item.name) + '</b>' + (item.caption ? ' — ' + escapeHtml(item.caption) : '');
    }
    function openLightbox(index) {
      currentIndex = index;
      renderLightbox();
      lightbox.classList.add('open');
    }
    function navigateLightbox(dir) {
      if (photos.length === 0) return;
      currentIndex = (currentIndex + dir + photos.length) % photos.length;
      renderLightbox();
    }

    document.querySelectorAll('#masonry .item img').forEach(img => {
      img.addEventListener('click', () => {
        const idx = parseInt(img.closest('.item').dataset.index, 10);
        openLightbox(idx);
      });
    });

    document.getElementById('lbClose').addEventListener('click', () => lightbox.classList.remove('open'));
    lightbox.addEventListener('click', (e) => { if (e.target === lightbox) lightbox.classList.remove('open'); });
    document.getElementById('lbPrev').addEventListener('click', (e) => { e.stopPropagation(); navigateLightbox(-1); });
    document.getElementById('lbNext').addEventListener('click', (e) => { e.stopPropagation(); navigateLightbox(1); });
    document.addEventListener('keydown', (e) => {
      if (!lightbox.classList.contains('open')) return;
      if (e.key === 'Escape') lightbox.classList.remove('open');
      if (e.key === 'ArrowLeft') navigateLightbox(-1);
      if (e.key === 'ArrowRight') navigateLightbox(1);
    });
    let touchStartX = null;
    lightbox.addEventListener('touchstart', (e) => { touchStartX = e.touches[0].clientX; });
    lightbox.addEventListener('touchend', (e) => {
      if (touchStartX === null) return;
      const dx = e.changedTouches[0].clientX - touchStartX;
      if (Math.abs(dx) > 50) navigateLightbox(dx > 0 ? -1 : 1);
      touchStartX = null;
    });
  </script>

</body>

</html>