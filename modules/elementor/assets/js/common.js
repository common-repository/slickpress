export function parseBooleanValues(obj) {
        for (var key in obj) {
            if (obj.hasOwnProperty(key)) {
                if (typeof obj[key] === "object") {
                    if (Array.isArray(obj[key])) {
                        obj[key] = obj[key].map(function(item) {
                            return parseBooleanValues(item);
                        });
                    } else {
                        parseBooleanValues(obj[key]);
                    }
                } else if (typeof obj[key] === "string") {
                    if (obj[key].toLowerCase() === "true") {
                        obj[key] = true;
                    } else if (obj[key].toLowerCase() === "false") {
                        obj[key] = false;
                    } else if (parseInt(obj[key]) > 0) {
                        obj[key] = parseInt(obj[key]);
                    }

                    if (key === "customPaging") {
                        try {
                            if (obj[key] === '') {
                                throw new Error('empty customPaging');
                            }
                            eval(`window.customFunction = ${(obj[key]).replaceAll('\\','')}`);
                        } catch (error) {
                            window.customFunction = undefined;
                        }

                        obj[key] = window.customFunction;
                        delete window.customFunction;
                    }
                }
            }
        }
        return obj;
    }
