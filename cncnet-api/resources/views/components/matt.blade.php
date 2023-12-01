<script type="module">
    import {
        tsParticles
    } from "https://cdn.jsdelivr.net/npm/tsparticles-engine/+esm";
    import {
        loadFull
    } from "https://cdn.jsdelivr.net/npm/tsparticles/+esm";

    async function loadParticles(options) {
        await loadFull(tsParticles);
        await tsParticles.load(options);
    }

    const configs = {
        particles: {
            move: {
                enable: true,
                speed: {
                    min: 1,
                    max: 6
                }
            },
            number: {
                value: 20,
                max: 30
            },
            opacity: {
                value: 1
            },
            rotate: {
                path: true
            },
            shape: {
                options: {
                    image: {
                        gif: false,
                        src: "/images/matt.png",
                        width: 34,
                        height: 38,
                    }
                },
                type: "image"
            },
            size: {
                value: {
                    min: 16,
                    max: 34
                }
            }
        }
    };

    loadParticles(configs);
</script>
