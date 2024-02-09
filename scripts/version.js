const fs = require("node:fs");
const path = require("node:path");
const glob = require("glob");

const pack = require("../package.json");

const globOptions = {
  ignore: [
    "node_modules/**",
    "scripts/**",
    "vendor/**",
  ],
};

const currentYear = String((new Date()).getFullYear());

glob("**/*.php", globOptions, (err, files) => {
  if (err) {
    throw err;
  }

  const now = (new Date()).getTime();

  files.forEach((file) => {
    const filepath = path.join(process.cwd(), file);

    fs.readFile(filepath, { encoding: "utf-8" }, (err, content) => {
      if (err) {
        throw err;
      }

      const data = content
        .replace(/define\( 'STANCER_WC_VERSION'.+/, `define( 'STANCER_WC_VERSION', '${pack.version}' );`)
        .replace(/define\( 'STANCER_ASSETS_VERSION'.+/, `define( 'STANCER_ASSETS_VERSION', '${now}' );`)
        .replace(/\* Version:.+/, `* Version:     ${pack.version}`)
        .replace(/\* @copyright (\d{4})(?:-\d{4})?\s+Stancer.+/, (_match, date) => {
          if (date === currentYear) {
            return `* @copyright ${date} Stancer / Iliad 78`;
          }

          return `* @copyright ${date}-${currentYear} Stancer / Iliad 78`;
        })
      ;

      fs.writeFile(file, data, (err) => {
        if (err) {
          throw err;
        }
      });
    });
  });
});

fs.readFile('README.txt', { encoding: "utf8" }, (err, content) => {
  if (err) {
    throw err;
  }

  fs.writeFile('README.txt', content.replace(/Stable tag:.+/, `Stable tag: ${pack.version}`), (err) => {
    if (err) {
      throw err;
    }
  });
});

fs.readFile('LICENSE', { encoding: "utf8" }, (err, content) => {
  if (err) {
    throw err;
  }

  const data = content
    .replace(/^Copyright \(c\) (\d{4})(?:-\d{4})?\s+Stancer.+/, (_match, date) => {
      if (date === currentYear) {
        return `Copyright (c) ${date} Stancer / Iliad 78`;
      }

      return `Copyright (c) ${date}-${currentYear} Stancer / Iliad 78`;
    })
  ;

  fs.writeFile('LICENSE', data, (err) => {
    if (err) {
      throw err;
    }
  });
});
