<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Coming Soon — Onninternational</title>
  <meta name="description" content="We're launching soon — sign up to get notified." />
  
</head>
<body>
  <main class="wrap" role="main">
    <div class="grid" aria-label="Coming soon section">
      <section>
        <div class="brand">
          <div class="logo" aria-hidden="true"></div>
          <div>
            <h1>Coming soon</h1>
          
          </div>
        </div>

        

      </section>

      
    </div>
  </main>

  <script>
    // === CONFIGURE LAUNCH DATE HERE ===
    // Set to a future date (YYYY, M-1, D, H, M, S)
    const LAUNCH = new Date(Date.UTC(2026, 0, 15, 12, 0, 0)); // Jan 15, 2026 12:00 UTC (example)

    // Friendly readable date
    document.getElementById('date-readable').textContent = LAUNCH.toUTCString();

    // Countdown logic
    function pad(n){ return String(n).padStart(2,'0') }
    function updateCountdown(){
      const now = new Date();
      const diff = LAUNCH - now;
      if (diff <= 0){
        document.getElementById('countdown').innerHTML = '<div style="color:var(--accent)"><strong>We are live!</strong></div>';
        return clearInterval(intervalId);
      }
      const s = Math.floor(diff/1000);
      const days = Math.floor(s/86400);
      const hours = Math.floor((s%86400)/3600);
      const minutes = Math.floor((s%3600)/60);
      const seconds = s%60;
      document.getElementById('days').textContent = pad(days);
      document.getElementById('hours').textContent = pad(hours);
      document.getElementById('minutes').textContent = pad(minutes);
      document.getElementById('seconds').textContent = pad(seconds);
    }
    const intervalId = setInterval(updateCountdown, 1000);
    updateCountdown();

    // Simple client-side subscribe stub (no backend). Replace with your POST endpoint.
    document.getElementById('signup').addEventListener('submit', function(e){
      e.preventDefault();
      const email = document.getElementById('email').value.trim();
      const msg = document.getElementById('msg');
      if (!email || !/.+@.+\..+/.test(email)){
        msg.textContent = 'Please enter a valid email address.';
        msg.style.color = '#ffb4b4';
        return;
      }

      // Replace with fetch to your API endpoint to store the email
      msg.textContent = 'Thanks — you are on the list!';
      msg.style.color = 'var(--accent)';
      document.getElementById('email').value = '';
    });

  </script>
</body>
</html>
