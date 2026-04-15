jQuery(document).ready(function ($){

  //Initial loading Hash
  let calcHash = 0;
  //Check if form was submitted
  let formSubmit = false;

  //----------------Generate initial hash----------------

  const loadingElements = document.querySelectorAll('[type="color"]');
  loadingElements.forEach(getHash)

  function getHash(item){
      calcHash = generateHash(item.value) ^ calcHash;
  }
  console.log("Loading Hash: "+ calcHash)

  //----------------Hash function----------------

  function generateHash(string){
      let hash = 0;
      let char;

      for (let i = 0; i < string.length; i++) {
          char = string.charCodeAt(i);
          hash = ((hash << 5) - hash) + char;
      }

      return Math.abs(hash);
  }

  //----------------Check form submit----------------

  var submit = document.getElementById('submit');
  submit.onclick = function() {
      formSubmit = true
  }

  //TODO Sollte Meldung geben ob man alles zurücksetzen möchte
  var resetDefaults = document.getElementById('resetDefaults');
  resetDefaults.onclick = function() {
      formSubmit = true
  }

  //----------------Check for change before leaving site----------------

  window.onbeforeunload = function(){
      let newHash = 0;
      const currentElements = document.querySelectorAll('[type="color"]');

      currentElements.forEach(newGetHash)

      function newGetHash(item){
          newHash = newHash ^ generateHash(item.value)
      }
      console.log("Exiting Hash: "+newHash)

      if (newHash !== calcHash && !formSubmit){
          //TODO Spachen Check
          return 'Are you sure you want to leave?';
      }
  };

  //---------------- toggle advanced settings ----------------
  $('.ebs_advanced_settings_toggle').click(function(){
    $('.ebs_advanced_settings').slideToggle();
    $(this).toggleClass('active');
  });


    function capitalizeFirstLetter(val) {
        return String(val).charAt(0).toUpperCase() + String(val).slice(1);
    }

  //---------------- fill all fields automatically and live preview----------------

  function convertHexToHSL(hex) {
      // Convert hex to RGB first
      let r = 0, g = 0, b = 0;
      if (hex.length == 4) {
        r = "0x" + hex[1] + hex[1];
        g = "0x" + hex[2] + hex[2];
        b = "0x" + hex[3] + hex[3];
      } else if (hex.length == 7) {
        r = "0x" + hex[1] + hex[2];
        g = "0x" + hex[3] + hex[4];
        b = "0x" + hex[5] + hex[6];
      }
      // Then to HSL
      r /= 255;
      g /= 255;
      b /= 255;
      let cmin = Math.min(r,g,b),
          cmax = Math.max(r,g,b),
          delta = cmax - cmin,
          h = 0,
          s = 0,
          l = 0;
    
      if (delta == 0)
        h = 0;
      else if (cmax == r)
        h = ((g - b) / delta) % 6;
      else if (cmax == g)
        h = (b - r) / delta + 2;
      else
        h = (r - g) / delta + 4;
    
      h = Math.round(h * 60);
    
      if (h < 0)
        h += 360;
    
      l = (cmax + cmin) / 2;
      s = delta == 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
      s = +(s * 100).toFixed(1);
      l = +(l * 100).toFixed(1);
    
      return [h, s, l];
  }

  function convertHSLToHex(hsl) {
      let h = hsl[0];
      let s = hsl[1];
      let l = hsl[2];
      s /= 100;
      l /= 100;
    
      let c = (1 - Math.abs(2 * l - 1)) * s,
          x = c * (1 - Math.abs((h / 60) % 2 - 1)),
          m = l - c/2,
          r = 0,
          g = 0, 
          b = 0; 
    
      if (0 <= h && h < 60) {
        r = c; g = x; b = 0;
      } else if (60 <= h && h < 120) {
        r = x; g = c; b = 0;
      } else if (120 <= h && h < 180) {
        r = 0; g = c; b = x;
      } else if (180 <= h && h < 240) {
        r = 0; g = x; b = c;
      } else if (240 <= h && h < 300) {
        r = x; g = 0; b = c;
      } else if (300 <= h && h < 360) {
        r = c; g = 0; b = x;
      }
      // Having obtained RGB, convert channels to hex
      r = Math.round((r + m) * 255).toString(16);
      g = Math.round((g + m) * 255).toString(16);
      b = Math.round((b + m) * 255).toString(16);
    
      // Prepend 0s, if necessary
      if (r.length == 1)
        r = "0" + r;
      if (g.length == 1)
        g = "0" + g;
      if (b.length == 1)
        b = "0" + b;
    
      return "#" + r + g + b;
  }

  function checkLightness(hsl){
      l = hsl[2];
      return l <= 65 ? "dark" : "light";
  }

  function colorPreview(colorInputName, newColor){
    $(':root').css('--'+colorInputName, newColor);
  }

  function autofillColors(){
      // get colors from main color fields
      let pc = $('.ebs_main_colors').find('#ebsPrimary').val();
      let sc = $('.ebs_main_colors').find('#ebsSecondary').val();
      let pcHSL = convertHexToHSL(pc);
      let scHSL = convertHexToHSL(sc);
      let pcLightness = checkLightness(pcHSL);
      let scLightness = checkLightness(scHSL);

      // colors based on scLightness
      let menuText = "#000000";
      let subMenu = convertHSLToHex([scHSL[0], scHSL[1], scHSL[2] - 10]);
      if(scLightness == "dark"){
          menuText = "#ffffff";
          subMenu = convertHSLToHex([scHSL[0], scHSL[1], scHSL[2] + 10]);
      }

      // colors based on pcLightness
      let buttonText = "#000000";
      if(pcLightness == "dark"){
          buttonText = "#ffffff";
      }

      // colors based directly on pc - shift values - 20% darker
      let linkHover = convertHSLToHex([pcHSL[0], pcHSL[1], pcHSL[2] - 20]);

      // colors based directly on pc - shift values - 10% darker
      let darker10 = convertHSLToHex([pcHSL[0], pcHSL[1], pcHSL[2] - 10]);

      // set all defined colors
      $('.ebs_advanced_settings input[type=text]').each(function(){
          let colorInputName = $(this).attr('name');
          let newColor = pc;

          // TODO:  1.ebsTertiary unten fest hinzugefügt, ok?
          //        2.Dynamische Farbwerte anpassen (Darker & Lighter)
          // TODO: Transform PrimaryColor hex to rgb
          switch(colorInputName) {
              case "ebsBackground"              :       newColor = '#f0f0f0';       break;
              case "ebsLinks"                   :       newColor = '#0073aa';       break;
              case "ebsLinksHover"              :       newColor = linkHover;       break;
              case "ebsPrimaryDarker20"         :       newColor = linkHover;       break;
              case "ebsPrimaryDarker10"         :       newColor = darker10;        break;
              case "ebsDisabledButtonText"      :       newColor = '#949494';       break;
              case "ebsDeleteLinks"             :       newColor = '#cc1818';       break;
              case "ebsDeleteLinksHover"        :       newColor = '#e63004';       break;
              case "ebsDisabledButtonBorder"    :       newColor = '#dddddd';       break;
              case "ebsPrimaryText"             :       newColor = menuText;        break;
              case "ebsTertiary"                :       newColor = '#096484';       break;
              case "ebsNotification"            :       newColor = '#e1a948';       break;
              case "ebsIcon"                    :       newColor = menuText;        break;
              case "ebsSubMenu"                 :       newColor = subMenu;         break;
              case "ebsSubMenuText"             :       newColor = menuText;        break;
              case "ebsSecondaryLighter"        :       newColor = sc;              break;

              /*
              case "menuText"           :       newColor = menuText;           break;
              case "baseMenu"           :       newColor = sc;                 break;
              case "subMenu"            :       newColor = subMenu;            break;
              case "notification"       :       newColor = '#d63638';          break;
              case "notificationText"   :       newColor = '#f0f0f1';          break;
              case "highlightText"      :       newColor = buttonText;         break;
              case "background"         :       newColor = '#f0f0f1';          break;
              case "linkHover"          :       newColor = linkHover;          break;
              case "buttonHover"        :       newColor = linkHover;          break;
              case "disabledButton"     :       newColor = '#969696';          break;
              case "disabledButtonText" :       newColor = '#000000';          break;
              case "icon"               :       newColor = menuText;           break;
              */
          }
          $(this).val(newColor);
          $('.wrapper_'+colorInputName).find('button.wp-color-result').css('background-color',newColor);
          colorPreview(colorInputName, newColor);
      });
      
  }

  // init all color pickers and listen to changes
  $(".ebs_colorPicker").wpColorPicker({
    change: function (event, ui) {
      var colorInputName = $(this).attr('name');
      var newColor = ui.color.toString();
      colorPreview(colorInputName, newColor);
    },
    clear: function (event) {
      var colorInputName = $(this).attr('name');
      var newColor = ui.color.toString();
      colorPreview(colorInputName, newColor);
    }
  });
  $(".ebs_mainColorPicker").wpColorPicker({
    change: function (event, ui) {
      autofillColors();
    },
    clear: function (event) {
      autofillColors();
    }
  });

});

