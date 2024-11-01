import { wpsp_e_general_controls, wpsp_e_all_controls, wpsp_slick_defaults } from './defaults.js';
export class WPSP_Settings_Manager {
  constructor(settings) {
    this.rawSettings = settings;
    this.wpsp_e_general_controls = wpsp_e_general_controls;
    this.wpsp_e_all_controls = wpsp_e_all_controls;
    this.wpsp_slick_defaults = wpsp_slick_defaults;
  }

  clean_elementor_container_settings() {
    let filteredSettings = Object.keys(this.rawSettings).filter(key => this.filterByPrefix(key));
    let cleanedSettings = {};

    filteredSettings.forEach(key => {
      let value = this.rawSettings[key];
      let newKey = key.replace(/^(responsive_|slick_)/, '');
      let keyType = this.control_type(newKey);
      if (keyType === 'switcher') {
        cleanedSettings[newKey] = (value === 'yes') ? 'true' : 'false';
      } else {
        cleanedSettings[newKey] = value;
      }
    });

    let responsiveBreakpoints = cleanedSettings.control || [];
    cleanedSettings.control = [];

    responsiveBreakpoints.forEach(breakpoint => {
      delete breakpoint._id;
      for (let key in breakpoint) {
        let value = breakpoint[key];
        let newKey = key.replace(/^responsive_/, '');
        delete breakpoint[key];
        let keyType = this.control_type(newKey);
        if (keyType === 'switcher') {
          breakpoint[newKey] = (value === 'yes') ? 'true' : 'false';
        } else {
          breakpoint[newKey] = value;
        }
        if (
          (
            (breakpoint.advance_mode === '' || breakpoint.responsive_advance_mode === '') ||
            (!breakpoint.advance_mode && !breakpoint.responsive_advance_mode)
          ) && !this.wpsp_e_general_controls.includes(newKey)
        ) {
          delete breakpoint[newKey];
        }
      }
    });

    cleanedSettings.responsive = responsiveBreakpoints.filter(breakpoint => {
      if (!breakpoint.breakpoint || !parseInt(breakpoint.breakpoint)) {
        return false;
      }
      let breakpt = breakpoint.breakpoint;
      delete breakpoint.breakpoint;
      breakpoint = {
        breakpoint: breakpt,
        settings: breakpoint
      };
      return true;
    });

    delete cleanedSettings.control;

    for (let key in cleanedSettings) {
      if (
        key !== 'responsive' &&
        (
          (cleanedSettings.advance_mode === '') ||
          !cleanedSettings.advance_mode
        ) && !this.wpsp_e_general_controls.includes(key)
      ) {
        delete cleanedSettings[key];
      }
    }

    cleanedSettings = this.cleanSettings(cleanedSettings, this.wpsp_slick_defaults);
    return cleanedSettings;
  }

  cleanSettings(settings, options) {
    for (let key in settings) {
      let value = settings[key];

      if (!(key in options)) {
        delete settings[key];
        continue;
      }

      if (!value) {
        delete settings[key];
        continue;
      }

      let keyType = this.control_type(key);
      if (value === options[key] || (keyType === 'switcher' && (value === 'true') === options[key])) {
        delete settings[key];
        continue;
      }

      if (key === 'responsive') {
        if (!Array.isArray(value)) {
          delete settings[key];
          continue;
        }
        value.forEach(brkpt => {
          for (let bkey in brkpt.settings) {
            let bval = brkpt.settings[bkey];
            if (!(bkey in options) || !bval || settings[bkey]) {
              delete brkpt.settings[bkey];
              continue;
            }
            let bkeyType = this.control_type(bkey);
            if (bval === options[bkey] || (bkeyType === 'switcher' && (bval === 'true') === options[bkey])) {
              delete brkpt.settings[bkey];
            }
          }
        });
        if (!value.length) {
          delete settings[key];
        }
        continue;
      }

      if (Array.isArray(value)) {
        value = this.cleanSettings(value, options);
        if (!value.length) {
          delete settings[key];
        }
      }
    }

    return settings;
  }

  filterByPrefix(key) {
    return key.startsWith('responsive_') || key.startsWith('slick_');
  }

  control_type(controlKey) {
    for (let [cKey, cValue] of Object.entries(this.wpsp_e_all_controls)) {
      if (controlKey in cValue) {
        return cKey;
      }
    }
    return '';
  }
}
