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
const currentDate = (new Date()).toISOString().split('T').at(0);


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
        .replace(/define\( 'STANCER_ASSETS_VERSION'.+/, `define( 'STANCER_ASSETS_VERSION', '${now}' );`)
        .replace(/define\( 'STANCER_WC_VERSION'.+/, `define( 'STANCER_WC_VERSION', '${pack.version}' );`)
        .replaceAll(/\* @since unreleased/g,`* @since ${pack.version}`)
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

fs.readFile('CHANGELOG.md', {encoding:"utf-8"}, (err,content) => {
  if (err) {
    throw err;
  }

  fs.writeFile(
    'CHANGELOG.md',
    content.replace(
      /##\s*\[?[uU]nreleased?\]?/,
      `## [${pack.version}] - ${currentDate}`
    ),
    (err) => {
      if (err) {
        throw err;
      }
    }
  )
});

fs.readFile('README.txt', { encoding: "utf8" }, (err, content) => {
  if (err) {
    throw err;
  }

  const data = content
    .replace(/Stable tag:.+/, `Stable tag: ${pack.version}`)
    .replace(/=\s+[uU]nreleased?\s+=/,`= ${pack.version} =`)
    ;

  fs.writeFile('README.txt', data, (err) => {
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
