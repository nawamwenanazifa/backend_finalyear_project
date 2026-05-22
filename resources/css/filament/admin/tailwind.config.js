import preset from '../../../../vendor/filament/filament/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                'fyn-primary': '#570013',
                'fyn-primary-container': '#800020',
                'fyn-secondary': '#7B5800',
                'fyn-background': '#FBF9F5',
            },
            fontFamily: {
                'sans': ['Manrope', 'sans-serif'],
                'serif': ['Noto Serif', 'serif'],
            },
        },
    },
}