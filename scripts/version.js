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

glob("**/*.php", globOptions, (err, files) => {
  if (err) {
    throw err;
  }

  files.forEach((file) => {
    const filepath = path.join(process.cwd(), file);

    fs.readFile(filepath, { encoding: "utf-8" }, (err, content) => {
      if (err) {
        throw err;
      }

      const data = content
        .replace(/define\( 'STANCER_VERSION'.+/, `define( 'STANCER_VERSION', '${pack.version}' );`)
        .replace(/\* Version:.+/, `* Version:     ${pack.version}`)
      ;

      fs.writeFile(file, data, (err) => {
        if (err) {
          throw err;
        }
      });
    });
  });
});
