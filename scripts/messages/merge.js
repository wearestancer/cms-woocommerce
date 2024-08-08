const fs = require("node:fs");
const exec = require("node:child_process").exec;
const path = require("node:path");
const glob = require("glob");

glob("languages/*.po", (err, files) => {
  if (err) {
    throw err;
  }

  files.forEach((file) => {
    const portable = path.join(process.cwd(), file);
    const template = portable.replace(/-[a-z]{2}_[A-Z]{2}\.po$/, '.pot');

    exec(`msgmerge ${portable} ${template} --update --backup=off`, (error) => {
      if (error) {
        throw error;
      }

      const current = new Date().getFullYear();
      const content = fs.readFileSync(portable, { encoding: 'utf-8' })
        .replace(/^# Copyright \(C\) .*/m, `# Copyright (C) 2023-${current} Stancer / Iliad 78`)
      ;

      fs.writeFileSync(portable, content, { encoding: 'utf-8' });
    });
  });
});
