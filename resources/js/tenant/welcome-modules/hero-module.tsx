import { Button } from '@/components/ui/button';
import { ArrowRight } from 'lucide-react';

interface HeroModuleProps {
    title: string;
    subtitle?: string;
    backgroundImage?: string;
    videoUrl?: string;
    brandColor?: string;
    ctas?: Array<{
        text: string;
        url: string;
        variant?: 'primary' | 'secondary';
    }>;
}

export function HeroModule({
    title,
    subtitle,
    backgroundImage,
    videoUrl,
    brandColor,
    ctas = [],
}: HeroModuleProps) {
    const bgStyle = backgroundImage
        ? {
              backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url(${backgroundImage})`,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
          }
        : {};

    return (
        <section
            className="relative px-6 py-24 text-white lg:px-8"
            style={{
                ...bgStyle,
                backgroundColor: brandColor || '#4B5563',
            }}
        >
            <div className="mx-auto max-w-4xl text-center">
                <h1 className="text-4xl font-bold tracking-tight sm:text-6xl">
                    {title}
                </h1>
                {subtitle && (
                    <p className="mt-6 text-lg leading-8">{subtitle}</p>
                )}

                {videoUrl && (
                    <div className="mx-auto mt-10 max-w-3xl">
                        <div className="aspect-video overflow-hidden rounded-lg shadow-2xl">
                            <iframe
                                src={videoUrl}
                                title="Welcome Video"
                                className="size-full"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowFullScreen
                            />
                        </div>
                    </div>
                )}

                {ctas.length > 0 && (
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        {ctas.map((cta, index) => (
                            <Button
                                key={index}
                                asChild
                                size="lg"
                                variant={
                                    cta.variant === 'secondary'
                                        ? 'outline'
                                        : 'default'
                                }
                                className={
                                    cta.variant !== 'secondary' && brandColor
                                        ? `bg-white hover:bg-gray-100`
                                        : ''
                                }
                                style={
                                    cta.variant !== 'secondary' && brandColor
                                        ? { color: brandColor }
                                        : undefined
                                }
                            >
                                <a href={cta.url}>
                                    {cta.text}
                                    <ArrowRight className="ml-2 size-4" />
                                </a>
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
