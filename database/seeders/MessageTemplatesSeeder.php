<?php

namespace Database\Seeders;

use App\Models\Church;
use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

/**
 * Starter message templates (Swedish). Only adds what a church is missing, so edited templates are never overwritten.
 */
class MessageTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Church::query()->cursor() as $church) {
            self::seedForChurch($church);
        }
    }

    public static function seedForChurch(Church $church): void
    {
        foreach (self::templates() as $template) {
            $exists = MessageTemplate::query()
                ->withoutGlobalScope('church')
                ->where('church_id', $church->id)
                ->where(fn ($query) => $query->where('key', $template['key'])->orWhere('name', $template['name']))
                ->exists();

            if ($exists) {
                continue;
            }

            MessageTemplate::query()->create([
                ...$template,
                'church_id' => $church->id,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @return list<array{name: string, key: string, subject: string, body: string, text_body: string, whatsapp_template: string}>
     */
    public static function templates(): array
    {
        return [
            [
                'name' => 'Kräva svar',
                'key' => 'krava-svar',
                'subject' => '{{church}}, {{task}},  {{event}},  {{date}}',
                'body' => <<<'HTML'
                    <p>Du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}. Var snäll och meddela oss om du kan eller inte kan komma och göra uppgiften igenom att trycka på en av knapparna nedan.</p>
                    <p>Tack och Gud Välsigna Dig!</p>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                      <tr>
                        <td>
                          <table border="0" cellspacing="0" cellpadding="0">
                            <tr>
                              <td align="center" bgcolor="#90EE90" style="border-radius: 6px; padding: 12px 24px;">
                                <a href="{{accept_url}}" target="_blank" style="font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; color: #14532D; text-decoration: none; display: block;">Ja, jag kommer</a>
                              </td>
                              <td width="12" style="font-size: 0; line-height: 0;">&nbsp;</td>
                              <td align="center" bgcolor="#FAA0A0" style="border-radius: 6px; padding: 12px 24px;">
                                <a href="{{decline_url}}" target="_blank" style="font-family: Helvetica, Arial, sans-serif; font-size: 16px; font-weight: bold; color: #7F1D1D; text-decoration: none; display: block;">Nej, jag kommer inte</a>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                    HTML,
                'text_body' => <<<'TEXT'
                    Du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}. Var snäll och meddela oss om du kan eller inte kan komma.
                    Ja, jag kommer  {{accept_link}}
                    Nej, jag kommer inte {{decline_link}}
                    TEXT,
                'whatsapp_template' => 'krava_svar',
            ],
            [
                'name' => 'Påminnelse endast',
                'key' => 'paminnelse-endast',
                'subject' => 'Påminnelse {{task}},  {{event}},  {{date}}',
                'body' => <<<'HTML'
                    <p>Detta är bara en påminnelse att du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}.</p>
                    <p>Tack och Gud Välsigna Dig!</p>
                    HTML,
                'text_body' => <<<'TEXT'
                    Detta är bara en påminnelse att du är schemalagt till att göra uppgiften {{task}} på {{event}}  {{date}}  {{time}}.
                    Tack och Gud Välsigna Dig!
                    TEXT,
                'whatsapp_template' => 'paminnelse',
            ],
        ];
    }
}
