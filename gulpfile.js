var gulp 		= require('gulp');
var gutil 		= require('gulp-util');
var notify 		= require('gulp-notify');
var sass 		= require('gulp-ruby-sass');
var autoprefix 	= require('gulp-autoprefixer');
var minifyCSS 	= require('gulp-minify-css');
var rename		= require('gulp-rename');
var include		= require('gulp-include');
var uglify		= require('gulp-uglify');
var shell		= require('gulp-shell');

gulp.task('main-css', function(){
	return sass('./assets/main.sass')
		.on('error', handleSassError)
		.pipe(autoprefix('last 3 version'))
		.pipe(minifyCSS({keepSpecialComments:0}))
        .pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest('./assets/public/css'))
	    .pipe(notify('compiled'));
});

gulp.task('main-js', function(){
	return gulp.src('./assets/main.js')
		.pipe(include())
		.pipe(uglify())
		.on('error', handleJsError)		
        .pipe(rename({suffix: '.min'}))
		.pipe(gulp.dest('./assets/public/js'))
	    .pipe(notify('compiled'));
});

gulp.task('watch', function(){
	gulp.watch('./assets/main.sass', ['main-css']);
	gulp.watch('./assets/main.js', ['main-js']);
});

gulp.task('default', ['main-css', 'main-js', 'watch']);

function handleJsError(err, line) {
	gulp.src('./assets/main.js').pipe(notify(err + ' ' + line));
	this.emit('end');
}

function handleSassError(err) {
	gulp.src('./assets/main.sass').pipe(notify(err));
	this.emit('end');
}