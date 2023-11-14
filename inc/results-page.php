<?php
// Create options page under woocommerce to display tickets
function sb_comp_entries_page()
{
  add_submenu_page(
    'woocommerce',
    'Competition Entries',
    'Competition Entries',
    'manage_options',
    'sb_competition_entries',
    'sb_competition_entries_page'
  );
}
add_action('admin_menu', 'sb_comp_entries_page');

// Add the ticket numbers to the order details page
function sb_competition_entries_page()
{
  // Get entries from the database
  global $wpdb;
  // Define your custom SQL query
  $query = "SELECT wp_posts.* FROM wp_posts INNER JOIN wp_postmeta ON ( wp_posts.ID = wp_postmeta.post_id ) WHERE 1=1 AND ( wp_postmeta.meta_key = '_sb_competition_tickets' ) GROUP BY wp_posts.ID ORDER BY wp_posts.post_date DESC";
  // Run the query and store the results in $results
  $results = $wpdb->get_results($query);

  // Build the array of orders to fetch
  $entries = [];
  $totalOrders = 0;
  foreach ($results as $row) {
    $tickets = get_post_meta($row->ID, '_sb_competition_tickets', true);
    foreach ($tickets as $ticket) {
      $order = wc_get_order($row->ID);
      $totalOrders++;
      $entries[] = [
        'date' => get_the_date('jS F Y', $row->ID),
        'order_id' => $row->ID,
        'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'ticket_no' => $ticket,
      ];
    }
  }
  $disable_winner = false;
  if(get_option('sb_winner_name') && get_option('sb_winner_ticket') && get_option('sb_comp_winner')){
    $disable_winner = true;
  }
  // Display the ticket numbers
  echo '<div class="wrap" style="display: flex; align-items: center; justify-content: space-between;padding-top: 0.5rem;">';
  echo '<div>';
  echo '<h1 class="wp-heading-inline" style=padding: 0;">Ticket Numbers</h1>';
  echo '<p><span>Total Orders: ' . count($results) . '</span> | <span>Total Tickets: ' . $totalOrders . '</span> | <a href="#" id="resetEntries">Reset Entries</a></p>';
  echo '</div>';
  echo '<div class="buttons">';
  echo '<a href="#" class="button-primary" onclick="downloadTableAsCSV(\'sb-competition-entries\', \'competition-entries.csv\')"style="margin-right: 10px;">Download as CSV</a>';
  if(!$disable_winner) {
    if(get_option('sb_comp_winner')){
      echo '<a href="#" class="button-primary pickWinner" onclick="pickRandomRow(\'sb-competition-entries\', \'disable\')">Pick Winner</a>';
    } else {
      echo '<a href="#" class="button-primary pickWinner" onclick="pickRandomRow(\'sb-competition-entries\')">Pick Winner</a>';
    }
  } else {
    echo '<a href="#" class="button-primary pickWinner" onclick="" disabled>Pick Winner</a>';
  }
  echo '</div>';
  echo '</div>';
  if(get_option('sb_winner_name') && get_option('sb_winner_ticket') && $disable_winner){
    echo '<div id="winner" class="winnerDecided">';
    echo '<h2>Winner: ' . get_option('sb_winner_name') . ' #' . get_option('sb_winner_ticket') . '</h2>';
    echo '</div>';
  } else {
    echo '<div id="winner"></div>';
  }
  echo '<table id="sb-competition-entries" class="widefat fixed striped" cellspacing="0">';
  echo '<thead><tr><th>Order Date</th><th>Order ID</th><th>Name</th><th>Ticket Number</th></tr></thead>';
  echo '<tbody>';
  // Check for errors
  if (!$results) {
    echo '<tr><td colspan="4">No entries found.</td></tr>';
    return;
  } else {
    foreach ($entries as $key => $entry) {
      // check if key is even number
      if ($key % 2 == 0) {
        echo '<tr class="alternate">';
      } else {
        echo '<tr>';
      }
      echo '<td>' . $entry['date'] . '</td>';
      echo '<td>' . $entry['order_id'] . '</td>';
      echo '<td id="customerName">' . $entry['name'] . '</td>';
      echo '<td id="ticketNo">' . $entry['ticket_no'] . '</td>';
      echo '</tr>';
    }
  }
  echo '</tbody>';
  echo '</table>';
}

function sb_comp_download()
{
  $css = '<style>
    @keyframes bang {
        from {
          transform: translate3d(0, 0, 0);
          opacity: 1;
        }
      }
      #winner {
        position: relative;
      }
      #winner i {
        position: absolute;
        display: block;
        left: 50%;
        top: 50%;
        width: 3px;
        height: 8px;
        background: red;
        opacity: 0;
        text-align: center;
      }
      .winnerDecided h2{
        color: red;
        text-align: center;
        font-size: 3rem;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-weight:bold;
        -webkit-margin-before: 0.3em;
        -webkit-margin-after: 0.2em;
        background-image: -webkit-linear-gradient(#FFF65C, #3A2C00);
        text-shadow: -1px -1px 1px rgba(255, 223, 0, 0.60);
        margin: 0;
        line-height: 1.4;
      </style>';

  $js = '<script>

    function downloadTableAsCSV(tableId, filename) {
        const table = document.getElementById(tableId);
        const rows = table.querySelectorAll("tr");
        const csv = [];

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i].querySelectorAll("td, th");
            const rowArray = Array.from(row).map(cell => cell.textContent);
            csv.push(rowArray.join(","));
        }

        const csvContent = "data:text/csv;charset=utf-8," + csv.join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
    }

    function pickRandomRow(tableId, disable) {
      let pickWinner = document.querySelectorAll(".pickWinner");
        if(disable){
          pickWinner.forEach(function (item) {
            item.setAttribute("disabled", "disabled");
          });
        }
        const table = document.getElementById(tableId);
        const tableBody = table.querySelector("tbody");
        const rows = tableBody.querySelectorAll("tr");
        if (rows.length === 0) {
            alert("No rows in the table.");
            return;
        }

        // AJAX request to check sb_comp_winner value
        jQuery.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
              action: "check_sb_comp_winner",
          },
          success: function (response) {
              console.log("Response from check_sb_comp_winner:", response);

              // if (response.sb_comp_winner === "1") {
                  // If sb_comp_winner is true, proceed to pick a winner and update post meta
                  const randomIndex = Math.floor(Math.random() * rows.length);
                  const randomRow = rows[randomIndex];
                  const winnerName = randomRow.querySelector("#customerName").textContent;
                  const winnerTicket = randomRow.querySelector("#ticketNo").textContent;

                  // Update post meta with winning details
                  jQuery.ajax({
                      url: ajaxurl,
                      type: "POST",
                      data: {
                          action: "update_winner_details",
                          winnerName: winnerName,
                          winnerTicket: winnerTicket,
                      },
                      success: function () {
                          // Display winner details or perform any other actions
                          const winner = document.getElementById("winner");
                          winner.classList.add("winnerDecided");
                          winner.innerHTML = "<h2>Winner: " + winnerName + " #" + winnerTicket + "</h2>";
                      },
                  });
              // } else {
              //     // If sb_comp_winner is false, display a message or take other actions
              //     alert("Competition winner not allowed at the moment.");
              // }
          },
          error: function (xhr, status, error) {
              console.error("AJAX error:", error);
          },
      });

        function random(max){
            return Math.random() * (max - 0) + 0;
        }

        var c = document.createDocumentFragment();
        for (var i=0; i<100; i++) {
          var styles = "transform: translate3d(" + (random(500) - 250) + "px, " + (random(200) - 150) + "px, 0) rotate(" + random(360) + "deg);\
                        background: hsla("+random(360)+",100%,50%,1);\
                        animation: bang 700ms ease-out forwards;\
                        opacity: 0";

          var e = document.createElement("i");
          e.style.cssText = styles.toString();
          c.appendChild(e);
      }

  // Globals
  var random = Math.random
    , cos = Math.cos
    , sin = Math.sin
    , PI = Math.PI
    , PI2 = PI * 2
    , timer = undefined
    , frame = undefined
    , confetti = [];

  var particles = 10
    , spread = 40
    , sizeMin = 3
    , sizeMax = 12 - sizeMin
    , eccentricity = 10
    , deviation = 100
    , dxThetaMin = -.1
    , dxThetaMax = -dxThetaMin - dxThetaMin
    , dyMin = .13
    , dyMax = .18
    , dThetaMin = .4
    , dThetaMax = .7 - dThetaMin;

  var colorThemes = [
    function() {
      return color(200 * random()|0, 200 * random()|0, 200 * random()|0);
    }, function() {
      var black = 200 * random()|0; return color(200, black, black);
    }, function() {
      var black = 200 * random()|0; return color(black, 200, black);
    }, function() {
      var black = 200 * random()|0; return color(black, black, 200);
    }, function() {
      return color(200, 100, 200 * random()|0);
    }, function() {
      return color(200 * random()|0, 200, 200);
    }, function() {
      var black = 256 * random()|0; return color(black, black, black);
    }, function() {
      return colorThemes[random() < .5 ? 1 : 2]();
    }, function() {
      return colorThemes[random() < .5 ? 3 : 5]();
    }, function() {
      return colorThemes[random() < .5 ? 2 : 4]();
    }
  ];
  function color(r, g, b) {
    return "rgb(" + r + "," + g + "," + b + ")";
  }

  // Cosine interpolation
  function interpolation(a, b, t) {
    return (1-cos(PI*t))/2 * (b-a) + a;
  }

  // Create a 1D Maximal Poisson Disc over [0, 1]
  var radius = 1/eccentricity, radius2 = radius+radius;
  function createPoisson() {
    // domain is the set of points which are still available to pick from
    // D = union{ [d_i, d_i+1] | i is even }
    var domain = [radius, 1-radius], measure = 1-radius2, spline = [0, 1];
    while (measure) {
      var dart = measure * random(), i, l, interval, a, b, c, d;

      // Find where dart lies
      for (i = 0, l = domain.length, measure = 0; i < l; i += 2) {
        a = domain[i], b = domain[i+1], interval = b-a;
        if (dart < measure+interval) {
          spline.push(dart += a-measure);
          break;
        }
        measure += interval;
      }
      c = dart-radius, d = dart+radius;

      // Update the domain
      for (i = domain.length-1; i > 0; i -= 2) {
        l = i-1, a = domain[l], b = domain[i];
        // c---d          c---d  Do nothing
        //   c-----d  c-----d    Move interior
        //   c--------------d    Delete interval
        //         c--d          Split interval
        //       a------b
        if (a >= c && a < d)
          if (b > d) domain[l] = d; // Move interior (Left case)
          else domain.splice(l, 2); // Delete interval
        else if (a < c && b > c)
          if (b <= d) domain[i] = c; // Move interior (Right case)
          else domain.splice(i, 0, c, d); // Split interval
      }

      // Re-measure the domain
      for (i = 0, l = domain.length, measure = 0; i < l; i += 2)
        measure += domain[i+1]-domain[i];
    }

    return spline.sort();
  }

  // Create the overarching container
  var container = document.createElement("div");
  container.style.position = "fixed";
  container.style.top      = "0";
  container.style.left     = "0";
  container.style.width    = "100%";
  container.style.height   = "0";
  container.style.overflow = "visible";
  container.style.zIndex   = "9999";

  // Confetto constructor
  function Confetto(theme) {
    this.frame = 0;
    this.outer = document.createElement("div");
    this.inner = document.createElement("div");
    this.outer.appendChild(this.inner);

    var outerStyle = this.outer.style, innerStyle = this.inner.style;
    outerStyle.position = "absolute";
    outerStyle.width  = (sizeMin + sizeMax * random()) + "px";
    outerStyle.height = (sizeMin + sizeMax * random()) + "px";
    innerStyle.width  = "100%";
    innerStyle.height = "100%";
    innerStyle.backgroundColor = theme();

    outerStyle.perspective = "50px";
    outerStyle.transform = "rotate(" + (360 * random()) + "deg)";
    this.axis = "rotate3D(" +
      cos(360 * random()) + "," +
      cos(360 * random()) + ",0,";
    this.theta = 360 * random();
    this.dTheta = dThetaMin + dThetaMax * random();
    innerStyle.transform = this.axis + this.theta + "deg)";

    this.x = window.innerWidth * random();
    this.y = -deviation;
    this.dx = sin(dxThetaMin + dxThetaMax * random());
    this.dy = dyMin + dyMax * random();
    outerStyle.left = this.x + "px";
    outerStyle.top  = this.y + "px";

    // Create the periodic spline
    this.splineX = createPoisson();
    this.splineY = [];
    for (var i = 1, l = this.splineX.length-1; i < l; ++i)
      this.splineY[i] = deviation * random();
    this.splineY[0] = this.splineY[l] = deviation * random();

    this.update = function(height, delta) {
      this.frame += delta;
      this.x += this.dx * delta;
      this.y += this.dy * delta;
      this.theta += this.dTheta * delta;

      // Compute spline and convert to polar
      var phi = this.frame % 7777 / 7777, i = 0, j = 1;
      while (phi >= this.splineX[j]) i = j++;
      var rho = interpolation(
        this.splineY[i],
        this.splineY[j],
        (phi-this.splineX[i]) / (this.splineX[j]-this.splineX[i])
      );
      phi *= PI2;

      outerStyle.left = this.x + rho * cos(phi) + "px";
      outerStyle.top  = this.y + rho * sin(phi) + "px";
      innerStyle.transform = this.axis + this.theta + "deg)";
      return this.y > height+deviation;
    };
  }

  function poof() {
    if (!frame) {
      // Append the container
      document.body.appendChild(container);

      // Add confetti
      var theme = colorThemes[0]
        , count = 0;
      (function addConfetto() {
        var confetto = new Confetto(theme);
        confetti.push(confetto);
        container.appendChild(confetto.outer);
        timer = setTimeout(addConfetto, spread * random());
      })(0);

      // Start the loop
      var prev = undefined;
      requestAnimationFrame(function loop(timestamp) {
        var delta = prev ? timestamp - prev : 0;
        prev = timestamp;
        var height = window.innerHeight;

        for (var i = confetti.length-1; i >= 0; --i) {
          if (confetti[i].update(height, delta)) {
            container.removeChild(confetti[i].outer);
            confetti.splice(i, 1);
          }
        }

        if (timer || confetti.length)
          return frame = requestAnimationFrame(loop);

        // Cleanup
        document.body.removeChild(container);
        frame = undefined;
      });
    }
  }

  poof();

    }
    jQuery("#resetEntries").on("click", function () {
      if (confirm("Are you sure you want to do this? This action cannot be undone!") == true) {
        jQuery.ajax({
          url: ajaxurl,
          type: "POST",
          data: { action: "sb_reset_entries"},
          success: function (data) {
            alert("Entries reset");
            location.reload();
          }
        });
      }
    });
</script>';
  echo $js;
  echo $css;
}
add_action('admin_footer', 'sb_comp_download');

function sb_reset_entries()
{
  delete_post_meta_by_key('_sb_competition_tickets');
  delete_option('sb_winner_name');
  delete_option('sb_winner_ticket');
}
add_action("wp_ajax_sb_reset_entries", "sb_reset_entries");

// Check sb_comp_winner value
function check_sb_comp_winner() {
  $sb_comp_winner = get_option('sb_comp_winner', false);

  // Return JSON response
  wp_send_json(['sb_comp_winner' => $sb_comp_winner]);
}
add_action('wp_ajax_check_sb_comp_winner', 'check_sb_comp_winner');

// Update post meta with winning details
function update_winner_details() {
  $winner_name = sanitize_text_field($_POST['winnerName']);
  $winner_ticket = sanitize_text_field($_POST['winnerTicket']);

  // Perform necessary actions with $winner_name and $winner_ticket
  // For example, update post meta with the winning details
  update_option('sb_winner_name', $winner_name);
  update_option('sb_winner_ticket', $winner_ticket);

  // Return success response
  wp_send_json(['success' => true]);
}
add_action('wp_ajax_update_winner_details', 'update_winner_details');
