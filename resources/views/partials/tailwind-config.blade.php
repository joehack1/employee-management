<script>
    const tealBrand = {
        50: '#edf8f7', 100: '#d9efed', 200: '#b8e1de', 300: '#8bcbc7',
        400: '#58b1ac', 500: '#35a29e', 600: '#1d9692', 700: '#187f7c',
        800: '#175553', 900: '#164745', 950: '#0b2928'
    };
    const redBrand = {
        50: '#fbf1f1', 100: '#f6e2e2', 200: '#edcaca', 300: '#dfa4a5',
        400: '#cb7476', 500: '#b54c4f', 600: '#a01e22', 700: '#861a1d',
        800: '#70191b', 900: '#5d191b', 950: '#330b0c'
    };
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    blue: tealBrand,
                    teal: tealBrand,
                    emerald: tealBrand,
                    indigo: tealBrand,
                    cyan: tealBrand,
                    green: tealBrand,
                    purple: tealBrand,
                    violet: tealBrand,
                    rose: redBrand,
                    red: redBrand,
                    amber: redBrand,
                    orange: redBrand,
                    yellow: redBrand,
                    brand: {
                        50: tealBrand[50],
                        100: tealBrand[100],
                        500: tealBrand[500],
                        600: tealBrand[600],
                        700: tealBrand[700],
                        800: tealBrand[800],
                        900: tealBrand[900],
                    }
                }
            }
        }
    }
</script>
