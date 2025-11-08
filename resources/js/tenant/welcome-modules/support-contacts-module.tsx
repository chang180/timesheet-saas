import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Mail, Phone, User } from 'lucide-react';

interface SupportContact {
    name: string;
    email?: string;
    phone?: string;
}

interface SupportContactsModuleProps {
    contacts: SupportContact[];
    brandColor?: string;
}

export function SupportContactsModule({
    contacts,
    brandColor,
}: SupportContactsModuleProps) {
    if (!contacts || contacts.length === 0) {
        return null;
    }

    return (
        <section className="bg-white px-6 py-16 lg:px-8 dark:bg-gray-800">
            <div className="mx-auto max-w-7xl">
                <div className="mx-auto max-w-2xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        聯絡支援
                    </h2>
                    <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        有任何問題嗎？歡迎聯絡我們的支援團隊
                    </p>
                </div>

                <div className="mx-auto mt-12 grid max-w-4xl gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {contacts.map((contact, index) => (
                        <Card key={index}>
                            <CardHeader>
                                <div
                                    className="mb-4 flex size-12 items-center justify-center rounded-lg"
                                    style={{
                                        backgroundColor: brandColor
                                            ? `${brandColor}20`
                                            : '#EFF6FF',
                                    }}
                                >
                                    <User
                                        className="size-6"
                                        style={{
                                            color: brandColor || '#3B82F6',
                                        }}
                                    />
                                </div>
                                <CardTitle className="text-lg">
                                    {contact.name}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-2">
                                {contact.email && (
                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <Mail className="size-4" />
                                        <a
                                            href={`mailto:${contact.email}`}
                                            className="hover:underline"
                                            style={
                                                brandColor
                                                    ? { color: brandColor }
                                                    : undefined
                                            }
                                        >
                                            {contact.email}
                                        </a>
                                    </div>
                                )}
                                {contact.phone && (
                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                        <Phone className="size-4" />
                                        <a
                                            href={`tel:${contact.phone}`}
                                            className="hover:underline"
                                            style={
                                                brandColor
                                                    ? { color: brandColor }
                                                    : undefined
                                            }
                                        >
                                            {contact.phone}
                                        </a>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </section>
    );
}
