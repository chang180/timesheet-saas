import { CheckCircle } from 'lucide-react';

interface QuickStartStep {
    title: string;
    description: string;
    icon?: string;
}

interface QuickStartStepsModuleProps {
    steps: QuickStartStep[];
    brandColor?: string;
}

export function QuickStartStepsModule({
    steps,
    brandColor,
}: QuickStartStepsModuleProps) {
    if (!steps || steps.length === 0) {
        return null;
    }

    return (
        <section className="bg-white px-6 py-16 lg:px-8 dark:bg-gray-800">
            <div className="mx-auto max-w-7xl">
                <div className="mx-auto max-w-2xl text-center">
                    <h2 className="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
                        快速開始
                    </h2>
                    <p className="mt-4 text-lg text-gray-600 dark:text-gray-300">
                        跟隨以下步驟，開始使用系統
                    </p>
                </div>

                <ol className="mx-auto mt-12 max-w-3xl space-y-8">
                    {steps.slice(0, 5).map((step, index) => (
                        <li key={index} className="flex gap-6">
                            <div
                                className="flex size-12 shrink-0 items-center justify-center rounded-full text-xl font-bold text-white"
                                style={{
                                    backgroundColor: brandColor || '#3B82F6',
                                }}
                            >
                                {index + 1}
                            </div>
                            <div className="flex-1">
                                <h3 className="text-xl font-semibold text-gray-900 dark:text-white">
                                    {step.title}
                                </h3>
                                <p className="mt-2 text-gray-600 dark:text-gray-300">
                                    {step.description}
                                </p>
                            </div>
                            <CheckCircle className="size-6 shrink-0 text-green-500" />
                        </li>
                    ))}
                </ol>
            </div>
        </section>
    );
}
