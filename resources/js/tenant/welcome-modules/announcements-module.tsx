import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Calendar } from 'lucide-react';

interface Announcement {
    title: string;
    content: string;
    publishedAt?: string;
}

interface AnnouncementsModuleProps {
    announcements: Announcement[];
    brandColor?: string;
}

export function AnnouncementsModule({
    announcements,
    brandColor,
}: AnnouncementsModuleProps) {
    if (!announcements || announcements.length === 0) {
        return null;
    }

    return (
        <section className="bg-gray-50 px-6 py-16 lg:px-8 dark:bg-gray-900">
            <div className="mx-auto max-w-7xl">
                <div className="mx-auto max-w-2xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        最新公告
                    </h2>
                </div>

                <div className="mx-auto mt-12 grid max-w-6xl gap-6 md:grid-cols-2">
                    {announcements.map((announcement, index) => (
                        <Card key={index}>
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <CardTitle>{announcement.title}</CardTitle>
                                    {announcement.publishedAt && (
                                        <Badge
                                            variant="secondary"
                                            className="ml-2"
                                            style={
                                                brandColor
                                                    ? {
                                                          backgroundColor: `${brandColor}20`,
                                                      }
                                                    : undefined
                                            }
                                        >
                                            <Calendar className="mr-1 size-3" />
                                            {new Date(
                                                announcement.publishedAt,
                                            ).toLocaleDateString('zh-TW')}
                                        </Badge>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                <CardDescription className="whitespace-pre-wrap">
                                    {announcement.content}
                                </CardDescription>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
