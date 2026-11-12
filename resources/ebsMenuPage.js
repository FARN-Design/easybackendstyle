jQuery(document).ready(function ($){

  const { __ } = wp.i18n;
  const form = document.getElementById('colorPickForm');
  const fields = () => [...form.querySelectorAll('.ebs_mainColorPicker, .ebs_colorPicker')].map(input => input.value).join('|');
  const initialValues = fields();
  let submitted = false;
  form.addEventListener('submit', function(event) {
    if (event.submitter && event.submitter.name === 'resetDefaults' && !window.confirm(__('Restore all default colors?', 'easybackendstyle'))) {
      event.preventDefault();
      return;
    }
    submitted = true;
  });
  window.addEventListener('beforeunload', function(event) {
    if (!submitted && fields() !== initialValues) {
      event.preventDefault();
      event.returnValue = '';
    }
  });

  //---------------- toggle advanced settings ----------------
  $('.ebs_advanced_settings_toggle').click(function(){
    $('.ebs_advanced_settings').slideToggle();
    $(this).toggleClass('active');
    $(this).attr('aria-expanded', $(this).hasClass('active') ? 'true' : 'false');
  });

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
      r = Math.min(255, Math.max(0, Math.round((r + m) * 255))).toString(16);
      g = Math.min(255, Math.max(0, Math.round((g + m) * 255))).toString(16);
      b = Math.min(255, Math.max(0, Math.round((b + m) * 255))).toString(16);
    
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
      const l = hsl[2];
      return l <= 65 ? "dark" : "light";
  }

  function readableLink(hex) {
    const luminance = value => {
      const channels = value.replace('#', '').match(/.{2}/g).map(c => {
        const v = parseInt(c, 16) / 255;
        return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
      });
      return channels[0] * 0.2126 + channels[1] * 0.7152 + channels[2] * 0.0722;
    };
    while ((luminance('#f0f0f0') + 0.05) / (luminance(hex) + 0.05) < 4.5) {
      const hsl = convertHexToHSL(hex);
      hex = convertHSLToHex([hsl[0], hsl[1], Math.max(0, hsl[2] - 1)]);
    }
    return hex;
  }

  function colorPreview(colorInputName, newColor){
    $(':root').css('--'+colorInputName, newColor);
    if (['ebsPrimary', 'ebsPrimaryDarker10', 'ebsPrimaryDarker20'].includes(colorInputName)) {
      let hex = newColor.replace('#', '');
      if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
      if (/^[0-9a-f]{6}$/i.test(hex)) {
        const rgb = hex.match(/.{2}/g).map(channel => parseInt(channel, 16)).join(', ');
        $(':root').css('--'+colorInputName+'RGB', rgb);
      }
    }
  }

  function updateHexCodeLabel() {
      $('.ebs_mainColorPicker, .ebs_colorPicker').each(function() {
          let colorInputName = $(this).attr('name');
          let newColor= $(this).val();

          $('.wrapper_'+colorInputName).find('.hex_code').text(newColor);
      })
  }

    function updatePencilIconColor (){
        $('.ebs_mainColorPicker, .ebs_colorPicker').each(function() {
            let colorInputName = $(this).attr('name');
            let newColor = $(this).val();

            let hsl = convertHexToHSL(newColor);

            if(checkLightness(hsl) === "dark"){
                $('.wrapper_'+colorInputName).find('.button.wp-color-result').addClass('is-dark');
            } else {
                $('.wrapper_'+colorInputName).find('.button.wp-color-result').removeClass('is-dark');
            }
        })
    }

    function autofillColors(){
      // get colors from main color fields
      let pc = $('.ebs_main_colors').find('#ebsPrimary').val();
      let sc = $('.ebs_main_colors').find('#ebsSecondary').val();
      let tc = $('.ebs_main_colors').find('#ebsTertiary').val();
      colorPreview('ebsPrimary', pc);
      colorPreview('ebsSecondary', sc);
      colorPreview('ebsTertiary', tc);
      let pcHSL = convertHexToHSL(pc);
      let scHSL = convertHexToHSL(sc);
      let tcHSL = convertHexToHSL(tc);
      let pcLightness = checkLightness(pcHSL);
      let scLightness = checkLightness(scHSL);
      let tcLightness = checkLightness(tcHSL);

      // colors based on scLightness
      let allText = "#000000";
      let subMenu = convertHSLToHex([scHSL[0], scHSL[1], scHSL[2] - 10]);
      if(scLightness == "dark"){
          allText = "#ffffff";
          subMenu = convertHSLToHex([scHSL[0], scHSL[1], scHSL[2] + 10]);
      }

      // colors based on pcLightness
      let buttonText = "#000000";
      if(pcLightness == "dark"){
          buttonText = "#ffffff";
      }

      //colors based on tcLightness
      let highlightedText = "#000000";
      if(tcLightness == "dark"){
          highlightedText = "#ffffff";
      }

      // colors based directly on pc - shift values - 20% darker
      let pcDarker20 = convertHSLToHex([pcHSL[0], pcHSL[1], pcHSL[2] - 20]);

      // colors based directly on pc - shift values - 10% darker
      let pcDarker10 = convertHSLToHex([pcHSL[0], pcHSL[1], pcHSL[2] - 10]);

      // set all defined colors
      $('.ebs_advanced_settings input[type=text]').each(function(){
          let colorInputName = $(this).attr('name');
          let newColor = pc;

          switch(colorInputName) {
              case "ebsBackground"              :       newColor = '#f0f0f0';       break;
              case "ebsLinks"                   :       newColor = readableLink(pc); break;
              case "ebsLinksHover"              :       newColor = readableLink(pcDarker20);      break;
              case "ebsPrimaryDarker20"         :       newColor = pcDarker20;      break;
              case "ebsPrimaryDarker10"         :       newColor = pcDarker10;      break;
              case "ebsDisabledButtonText"      :       newColor = '#949494';       break;
              case "ebsDeleteLinks"             :       newColor = '#cc1818';       break;
              case "ebsDeleteLinksHover"        :       newColor = '#e63004';       break;
              case "ebsPrimaryText"             :       newColor = allText;         break;
              case "ebsMenuHover"               :       newColor = convertHSLToHex([pcHSL[0], pcHSL[1], pcHSL[2] + 10]); break;
              case "ebsNotification"            :       newColor = tc;              break;
              case "ebsIcon"                    :       newColor = allText;         break;
              case "ebsSubMenu"                 :       newColor = subMenu;         break;
              case "ebsSubMenuText"             :       newColor = allText;         break;
              case "ebsSecondaryLighter"        :       newColor = subMenu;         break;
              case "ebsHighlightedText"         :       newColor = highlightedText; break;
              case "ebsPrimaryButtonText"       :       newColor = buttonText;      break;
          }
          $(this).val(newColor);
          $('.wrapper_'+colorInputName).find('button.wp-color-result').css('background-color',newColor);
          colorPreview(colorInputName, newColor);
      });
  }
  // Imports update multiple pickers. Refresh from their final values without
  // recalculating advanced colors returned by the server.
  form.addEventListener('ebs:colors-imported', function () {
    $('.ebs_mainColorPicker, .ebs_colorPicker').each(function () {
      colorPreview(this.name, this.value);
    });
    updateHexCodeLabel();
    updatePencilIconColor();
  });

  // init all color pickers and listen to changes
  $(".ebs_colorPicker").wpColorPicker({
    change: function (event, ui) {
      var colorInputName = $(this).attr('name');
      var newColor = ui.color.toString();
      colorPreview(colorInputName, newColor);
      updateHexCodeLabel(this);
      updatePencilIconColor(colorInputName, newColor);
    },
    clear: function (event) {
      var colorInputName = $(this).attr('name');
      var newColor = $(this).data('default-color');
      $(this).val(newColor);
      colorPreview(colorInputName, newColor);
      updateHexCodeLabel(this);
      updatePencilIconColor(colorInputName, newColor);
    }
  });
  $(".ebs_mainColorPicker").wpColorPicker({
    change: function (event, ui) {
      // Iris may emit change before the text input contains the new color.
      $(this).val(ui.color.toString());
      autofillColors();
      updateHexCodeLabel();
      updatePencilIconColor();
    },
    clear: function (event) {
      autofillColors();
      updateHexCodeLabel();
      updatePencilIconColor();
    }
  });

  // Use the translated field label for the color swatch's accessible name.
  $('.ebs_mainColorPicker, .ebs_colorPicker').each(function () {
    const label = $('label[for="' + this.id + '"]').first().text().trim();
    $(this).closest('.wp-picker-container').find('button.wp-color-result').attr('aria-label', label);
  });

  // Moving the buttons generated by WP within the Iris Picker
  let spanButtons = document.querySelectorAll(".wp-picker-input-wrap");

    spanButtons.forEach(function(button) {
        let pickerHolder = button.nextElementSibling;
        let innerContainer = pickerHolder.querySelector(".iris-picker");
        if (innerContainer) innerContainer.prepend(button);
    })
});

