const { src, dest, series, watch } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');

// Компиляция SASS в CSS
function compileSass() {
  return src('src/scss/**/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(dest('dist/css'));
}

// Минификация CSS
function minifyCSS() {
  return src('dist/css/styles.css')
    .pipe(cleanCSS())
    .pipe(rename({ suffix: '.min' }))
    .pipe(dest('dist/css'));
}

// Копируем папку fonts в dist
function copyFonts() {
    return src('fonts/*')
      .pipe(dest('dist/fonts'));
}


// Задача Watch для отслеживания изменений
function watchFiles() {
  watch('src/scss/**/*.scss', series(compileSass, minifyCSS));
}

// Задачи, выполняемые по умолчанию
exports.default = series(copyFonts, compileSass, minifyCSS);

// Отдельная задача для минификации (если нужно запустить вручную)
exports.minify = minifyCSS;

// Задача для разработки (с отслеживанием изменений)
exports.dev = series(copyFonts, compileSass, minifyCSS, watchFiles);